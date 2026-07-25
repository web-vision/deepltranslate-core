<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Hooks;

use Symfony\Contracts\Service\Attribute\Required;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\SysLog\Action\Database as SystemLogDatabaseAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Domain\Dto\InlineParentReference;
use WebVision\Deepltranslate\Core\Domain\Dto\TranslateContext;
use WebVision\Deepltranslate\Core\Domain\Repository\PageRepository;
use WebVision\Deepltranslate\Core\Exception\LanguageIsoCodeNotFoundException;
use WebVision\Deepltranslate\Core\Exception\LanguageRecordNotFoundException;
use WebVision\Deepltranslate\Core\Service\DeeplService;
use WebVision\Deepltranslate\Core\Service\InlineRelationResolver;
use WebVision\Deepltranslate\Core\Service\LanguageService;
use WebVision\Deepltranslate\Core\Service\ProcessingInstruction;
use WebVision\Deepltranslate\Core\Service\RecordLocalizationResolverInterface;

abstract class AbstractTranslateHook
{
    /** @phpstan-ignore property.uninitializedReadonly */
    protected readonly DeeplService $deeplService;
    /** @phpstan-ignore property.uninitializedReadonly */
    protected readonly PageRepository $pageRepository;
    /** @phpstan-ignore property.uninitializedReadonly */
    protected readonly LanguageService $languageService;
    /** @phpstan-ignore property.uninitializedReadonly */
    protected readonly ProcessingInstruction $processingInstruction;
    /** @phpstan-ignore property.uninitializedReadonly */
    protected readonly InlineRelationResolver $inlineRelationResolver;
    /** @phpstan-ignore property.uninitializedReadonly */
    protected readonly RecordLocalizationResolverInterface $recordLocalizationResolver;

    #[Required]
    final public function injectInlineRelationResolver(InlineRelationResolver $inlineRelationResolver): void
    {
        /** @phpstan-ignore property.readOnlyAssignNotInConstructor */
        $this->inlineRelationResolver = $inlineRelationResolver;
    }

    #[Required]
    final public function injectRecordLocalizationResolver(RecordLocalizationResolverInterface $recordLocalizationResolver): void
    {
        /** @phpstan-ignore property.readOnlyAssignNotInConstructor */
        $this->recordLocalizationResolver = $recordLocalizationResolver;
    }

    #[Required]
    final public function injectPageRepository(PageRepository $pageRepository): void
    {
        /** @phpstan-ignore property.readOnlyAssignNotInConstructor */
        $this->pageRepository = $pageRepository;
    }

    #[Required]
    final public function injectDeeplService(DeeplService $deeplService): void
    {
        /** @phpstan-ignore property.readOnlyAssignNotInConstructor */
        $this->deeplService = $deeplService;
    }

    #[Required]
    final public function injectLanguageService(LanguageService $languageService): void
    {
        /** @phpstan-ignore property.readOnlyAssignNotInConstructor */
        $this->languageService = $languageService;
    }

    #[Required]
    final public function injectProcessingInstruction(ProcessingInstruction $processingInstruction): void
    {
        /** @phpstan-ignore property.readOnlyAssignNotInConstructor */
        $this->processingInstruction = $processingInstruction;
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
        return match ($tableName) {
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
        // @see https://github.com/web-vision/deepltranslate-core/issues/503
        $inlineParentReference = $this->inlineRelationResolver->resolveParentReference($table, (int)$id);
        if ($inlineParentReference !== null) {
            $this->localizeInlineChildRecord($inlineParentReference, (int)$value, $dataHandler);
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
        $parentHasTranslation = $this->recordLocalizationResolver->hasTranslation(
            $reference->parentTable,
            $reference->parentUid,
            $languageId
        );
        if ($parentHasTranslation === false) {
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
            $dataHandler->log(
                $reference->childTable,
                $reference->childUid,
                SystemLogDatabaseAction::LOCALIZE,
                null,
                SystemLogErrorClassification::MESSAGE,
                $message,
                null,
                $messageData
            );
            $this->flashMessages(
                str_replace(
                    array_map(static fn(string $key): string => '{' . $key . '}', array_keys($messageData)),
                    array_map(strval(...), array_values($messageData)),
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
}
