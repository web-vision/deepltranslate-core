<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Hooks;

use Symfony\Contracts\Service\Attribute\Required;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Domain\Dto\InlineParentReference;
use WebVision\Deepltranslate\Core\Domain\Dto\TranslateContext;
use WebVision\Deepltranslate\Core\Domain\Enum\InlineParentState;
use WebVision\Deepltranslate\Core\Domain\Repository\PageRepository;
use WebVision\Deepltranslate\Core\Exception\LanguageIsoCodeNotFoundException;
use WebVision\Deepltranslate\Core\Exception\LanguageRecordNotFoundException;
use WebVision\Deepltranslate\Core\Service\DeeplService;
use WebVision\Deepltranslate\Core\Service\InlineRelationResolver;
use WebVision\Deepltranslate\Core\Service\LanguageService;
use WebVision\Deepltranslate\Core\Service\ProcessingInstruction;

abstract class AbstractTranslateHook
{
    protected DeeplService $deeplService;

    protected PageRepository $pageRepository;

    protected LanguageService $languageService;
    protected ProcessingInstruction $processingInstruction;

    protected InlineRelationResolver $inlineRelationResolver;

    public function __construct(
        PageRepository $pageRepository,
        DeeplService $deeplService,
        LanguageService $languageService,
        ProcessingInstruction $processingInstruction
    ) {
        $this->deeplService = $deeplService;
        $this->pageRepository = $pageRepository;
        $this->languageService = $languageService;
        $this->processingInstruction = $processingInstruction;
    }

    /**
     * Setter injection to avoid changing the constructor signature within a maintenance branch.
     */
    #[Required]
    final public function injectInlineRelationResolver(InlineRelationResolver $inlineRelationResolver): void
    {
        $this->inlineRelationResolver = $inlineRelationResolver;
    }

    /**
     * These logics were outsourced to test them and later to resolve them in a service
     *
     * @deprecated Please use this function @see DeeplService::translateContent()
     */
    public function translateContent(
        string $content,
        string $sourceLanguageIsocode,
        string $targetLanguageIsocode
    ): string {
        return $this->deeplService->translateRequest(
            $content,
            $targetLanguageIsocode,
            $sourceLanguageIsocode
        );
    }

    /**
     * @internal
     * @deprecated Use {self::createTranslateContextForRecords()} instead.
     *
     * @throws LanguageRecordNotFoundException
     * @throws LanguageIsoCodeNotFoundException
     */
    protected function createTranslateContext(string $content, int $targetLanguageUid, Site $site): TranslateContext
    {
        $context = new TranslateContext($content);
        $sourceLanguageRecord = $this->languageService->getSourceLanguage($site);
        $context->setSourceLanguageCode($sourceLanguageRecord['languageCode']);

        try {
            $targetLanguageRecord = $this->languageService->getTargetLanguage($site, $targetLanguageUid);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Target language not supported by DeepL. Possibly wrong Site configuration. Message: %s',
                    $e->getMessage(),
                ),
                1746962367,
                $e,
            );
        }

        $context->setTargetLanguageCode($targetLanguageRecord['languageCode']);
        if (
            $targetLanguageRecord['formality'] !== ''
            && $this->deeplService->hasLanguageFormalitySupport($targetLanguageRecord['languageCode'])
        ) {
            $context->setFormality($targetLanguageRecord['formality']);
        }

        return $context;
    }

    /**
     * @internal
     *
     * @param array{uid: int, title: string, language_isocode: string, languageCode: string} $sourceLanguageRecord
     * @param array{uid: int, title: string, language_isocode: string, languageCode: string, formality: string} $targetLanguageRecord
     *
     * @throws LanguageRecordNotFoundException
     * @throws LanguageIsoCodeNotFoundException
     */
    protected function createTranslateContextForRecords(string $content, array $sourceLanguageRecord, array $targetLanguageRecord): TranslateContext
    {
        $context = new TranslateContext($content);
        $context->setSourceLanguageCode($sourceLanguageRecord['languageCode']);
        $context->setTargetLanguageCode($targetLanguageRecord['languageCode']);

        if (
            $targetLanguageRecord['formality'] !== ''
            && $this->deeplService->hasLanguageFormalitySupport($targetLanguageRecord['languageCode'])
        ) {
            $context->setFormality($targetLanguageRecord['formality']);
        }

        return $context;
    }

    /**
     * @param array<string, mixed>|int $currentRecord
     */
    protected function findCurrentParentPage(string $tableName, int|array $currentRecord): int
    {
        if (is_int($currentRecord)) {
            /** @var array<string, mixed> $currentRecord */
            $currentRecord = BackendUtility::getRecord($tableName, $currentRecord);
            if (!is_array($currentRecord)) {
                return 0;
            }
        }
        return match($tableName) {
            'pages' => (int)($currentRecord['uid'] ?? 0),
            default => (int)($currentRecord['pid'] ?? 0),
        };
    }

    protected function flashMessages(string $message, string $title, ContextualFeedbackSeverity $severity): void
    {
        if (Environment::isCli() || Environment::getContext()->isTesting()) {
            return;
        }

        $flashMessage = new FlashMessage($message, $title, $severity);
        GeneralUtility::makeInstance(FlashMessageService::class)
            ->getMessageQueueByIdentifier()
            ->addMessage($flashMessage);
    }

    /**
     * @param string $id
     * @param mixed $value
     * @param int $pasteUpdate
     */
    public function processCmdmap(
        string $command,
        string $table,
        $id,
        $value,
        bool &$commandIsProcessed,
        DataHandler $dataHandler,
        $pasteUpdate
    ): void {
        if ($command !== 'deepltranslate' || $commandIsProcessed !== false) {
            return;
        }
        $this->processingInstruction->setProcessingInstruction($table, $id, true);

        // Inline (IRRE) children in connected mode must not be localized on their own: `DataHandler::localize()`
        // does not write the `foreign_field` pointer to the translated parent, which would create a translation
        // not attached to anything. Hand these over to the DataHandler command dealing with inline children.
        //
        // A record whose table is used for inline children but which is not one itself - most prominently a
        // `tt_content` element placed directly on a page - resolves to `NotInlineChild` and is localized
        // normally below. A broken relation configuration (`Ambiguous`, `ParentMissing`) is skipped and
        // reported to the editor instead of silently producing a mis-attached translation.
        // @see https://github.com/web-vision/deepltranslate-core/issues/503
        $inlineParentResolution = $this->inlineRelationResolver->resolveParentReference($table, (int)$id);
        $inlineParentReference = $inlineParentResolution->reference;
        if ($inlineParentReference !== null) {
            $this->localizeInlineChildRecord($inlineParentReference, (int)$value, $dataHandler);
            $commandIsProcessed = true;
            return;
        }
        if ($inlineParentResolution->state === InlineParentState::Ambiguous
            || $inlineParentResolution->state === InlineParentState::ParentMissing
        ) {
            $this->logBrokenInlineRelation($inlineParentResolution->state, $table, (int)$id);
            $commandIsProcessed = true;
            return;
        }

        // Following lines are copied from `DataHandler::process_cmdmap()` from 'localize' command switch. Property
        // is protected and the reason we need to use PHP powerfull reflection API to set the wanted value.
        $dataHandlerPropertyReflection = (new \ReflectionProperty($dataHandler, 'useTransOrigPointerField'));
        $backupUseTransOrigPointerField = $dataHandlerPropertyReflection->getValue($dataHandler);
        $dataHandlerPropertyReflection->setValue($dataHandler, true);
        $dataHandler->localize($table, (int)$id, $value);
        $dataHandlerPropertyReflection->setValue($dataHandler, $backupUseTransOrigPointerField);

        $commandIsProcessed = true;
    }

    /**
     * Localizes an inline child record through its parent record, so TYPO3 writes the `foreign_field`
     * pointer, the `pid` and the sorting of the created translation.
     *
     * Without a translated parent record the child translation cannot be attached to anything. In that
     * case nothing is created at all, mirroring `DataHandler::inlineLocalizeSynchronize()`, which refuses
     * to work on a parent record without localization, too.
     */
    private function localizeInlineChildRecord(
        InlineParentReference $reference,
        int $languageId,
        DataHandler $dataHandler
    ): void {
        // Note: a translation with an empty `l10n_source` is not found by this lookup, because it
        // matches `ctrl.translationSource` and only falls back to `ctrl.transOrigPointerField` for
        // tables without a translation source field. Such records are created by TYPO3 itself, for
        // example through a plain DataHandler datamap, so an existing parent translation can be
        // missed here and the child translation is skipped.
        //
        // This cannot be worked around inside the extension: `DataHandler::inlineLocalizeSynchronize()`,
        // which the localization is handed over to, resolves the parent localization with the very
        // same call, and further DataHandler code paths do so as well. Only the upstream fix repairs
        // the behaviour:
        //
        // - https://forge.typo3.org/issues/110281
        // - main: https://review.typo3.org/c/Packages/TYPO3.CMS/+/94914
        // - 14.3: https://review.typo3.org/c/Packages/TYPO3.CMS/+/94916
        // - 13.4: https://review.typo3.org/c/Packages/TYPO3.CMS/+/94915
        //
        // The fix is released with TYPO3 v13.4.34 and v14.3.6, and the minimum TYPO3 v13 version
        // required by this extension contains it. TYPO3 v12.4 has reached ELTS and no longer
        // receives fixes from the public community, so it will never contain the change. Instances
        // on v12.4 have to apply the patch shipped in `Documentation/CorePatches/` themselves, see
        // the "Known issues" chapter of the extension documentation.
        //
        // @todo Drop this note once the TYPO3 v12.4 support is dropped.
        $translatedParentRecords = BackendUtility::getRecordLocalization(
            $reference->parentTable,
            $reference->parentUid,
            $languageId
        );
        if (!is_array($translatedParentRecords) || $translatedParentRecords === []) {
            $message = 'DeepL translation of inline child record "{table}:{uid}" has been skipped, because parent'
                . ' record "{parentTable}:{parentUid}" has no translation for language "{language}" yet. Translate'
                . ' the parent record first.';
            $messageData = [
                'table' => $reference->childTable,
                'uid' => $reference->childUid,
                'parentTable' => $reference->parentTable,
                'parentUid' => $reference->parentUid,
                'language' => $languageId,
            ];
            // `DataHandler::log()` has incompatible signatures in TYPO3 v12 and v13, therefore the
            // extension logger is used here instead of writing a `sys_log` entry.
            GeneralUtility::makeInstance(LogManager::class)
                ->getLogger(__CLASS__)
                ->info($message, $messageData);
            $this->flashMessages(
                str_replace(
                    array_map(static fn (string $key): string => '{' . $key . '}', array_keys($messageData)),
                    array_map(static fn ($value): string => (string)$value, array_values($messageData)),
                    $message
                ),
                '',
                ContextualFeedbackSeverity::WARNING
            );
            return;
        }

        $inlineDataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $inlineDataHandler->start([], [
            $reference->parentTable => [
                $reference->parentUid => [
                    'inlineLocalizeSynchronize' => [
                        'field' => $reference->parentField,
                        'language' => $languageId,
                        'action' => 'localize',
                        'ids' => [$reference->childUid],
                    ],
                ],
            ],
        ]);
        $inlineDataHandler->process_cmdmap();
        $dataHandler->errorLog = array_merge($dataHandler->errorLog, $inlineDataHandler->errorLog);
    }

    /**
     * Reports a record whose inline relation could not be resolved reliably - an ambiguous relation
     * configuration or a missing parent record. Such a record is skipped instead of being localized
     * on its own, which would attach the translation to the wrong parent or to nothing at all. A
     * broken TCA or broken data is the likely cause, so the editor is informed.
     */
    private function logBrokenInlineRelation(InlineParentState $state, string $table, int $uid): void
    {
        $message = $state === InlineParentState::Ambiguous
            ? 'DeepL translation of record "{table}:{uid}" has been skipped, because it matches more than one'
                . ' inline parent relation and cannot be attached reliably. Check the inline relation'
                . ' configuration (TCA) of this table.'
            : 'DeepL translation of inline child record "{table}:{uid}" has been skipped, because the parent'
                . ' record it points to does not exist. Check the record\'s inline relation.';
        $messageData = [
            'table' => $table,
            'uid' => $uid,
        ];
        // `DataHandler::log()` has incompatible signatures in TYPO3 v12 and v13, therefore the
        // extension logger is used here instead of writing a `sys_log` entry.
        GeneralUtility::makeInstance(LogManager::class)
            ->getLogger(__CLASS__)
            ->info($message, $messageData);
        $this->flashMessages(
            str_replace(
                array_map(static fn (string $key): string => '{' . $key . '}', array_keys($messageData)),
                array_map(static fn ($value): string => (string)$value, array_values($messageData)),
                $message
            ),
            '',
            ContextualFeedbackSeverity::WARNING
        );
    }
}
