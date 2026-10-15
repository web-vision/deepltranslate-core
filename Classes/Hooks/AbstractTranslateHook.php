<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Hooks;

use Symfony\Contracts\Service\Attribute\Required;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\ReferenceIndexUpdater;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\SysLog\Action\Database as SystemLogDatabaseAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
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
use WebVision\Deepltranslate\Core\Service\NestedDataHandlerSettingsInterface;
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
    /** @phpstan-ignore property.uninitializedReadonly */
    protected readonly NestedDataHandlerSettingsInterface $nestedDataHandlerSettings;

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
    final public function injectNestedDataHandlerSettings(NestedDataHandlerSettingsInterface $nestedDataHandlerSettings): void
    {
        /** @phpstan-ignore property.readOnlyAssignNotInConstructor */
        $this->nestedDataHandlerSettings = $nestedDataHandlerSettings;
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

    /**
     * A message stored in the session reaches the editor on the next backend page, also when the translation runs
     * in an AJAX request of the localization wizard or before a redirect. In the testing context the messages are
     * queued, so tests can check them.
     */
    protected function flashMessages(
        string $message,
        string $title,
        ContextualFeedbackSeverity $severity,
        bool $storeInSession = false,
    ): void {
        if (Environment::isCli() && !Environment::getContext()->isTesting()) {
            return;
        }

        $flashMessage = new FlashMessage($message, $title, $severity, $storeInSession);
        GeneralUtility::makeInstance(FlashMessageService::class)
            ->getMessageQueueByIdentifier()
            ->addMessage($flashMessage);
    }

    /**
     * @param int|string $id
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
        // A command issued while another one is running, by a DataHandler hook of its localization for example,
        // restores the processing instruction of the running command when it is done. The remaining fields of the
        // running localization would otherwise be resolved against the record of this command: its site, its page
        // and the glossary and the translation context of that page.
        $runningInstruction = $this->processingInstruction->startCommand($table, $id);
        try {
            $this->processDeeplTranslateCommand($table, $id, (int)$value, $dataHandler);
        } finally {
            $this->processingInstruction->endCommand($runningInstruction);
        }
        $commandIsProcessed = true;
    }

    private function processDeeplTranslateCommand(string $table, int|string $id, int $languageId, DataHandler $dataHandler): void
    {
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
            $this->localizeInlineChildRecord($inlineParentReference, $languageId, $dataHandler);
            return;
        }
        if ($inlineParentResolution->state === InlineParentState::Ambiguous
            || $inlineParentResolution->state === InlineParentState::ParentMissing
        ) {
            $this->logBrokenInlineRelation($inlineParentResolution->state, $table, (int)$id, $dataHandler);
            return;
        }

        // Following lines are copied from `DataHandler::process_cmdmap()` from 'localize' command switch. Property
        // is protected and the reason we need to use PHP powerfull reflection API to set the wanted value.
        $dataHandlerPropertyReflection = (new \ReflectionProperty($dataHandler, 'useTransOrigPointerField'));
        $backupUseTransOrigPointerField = $dataHandlerPropertyReflection->getValue($dataHandler);
        $dataHandlerPropertyReflection->setValue($dataHandler, true);
        $dataHandler->localize($table, (int)$id, $languageId);
        $dataHandlerPropertyReflection->setValue($dataHandler, $backupUseTransOrigPointerField);
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
        // Note: the underlying TYPO3 lookup matches `ctrl.translationSource` (`l10n_source`) and only
        // falls back to `ctrl.transOrigPointerField` (`l10n_parent`) for tables without a translation
        // source field, so a translation with an empty `l10n_source` - created by TYPO3 itself, for
        // example through a plain DataHandler datamap - is not found. An existing parent translation
        // is then missed and the child translation is skipped.
        //
        // The behaviour cannot be repaired from within the extension: `DataHandler::inlineLocalizeSynchronize()`,
        // which the localization is handed over to below, resolves the parent localization with the
        // same lookup, and further DataHandler code paths do so as well. Only the upstream fix helps:
        //
        // - https://forge.typo3.org/issues/110281
        // - main: https://review.typo3.org/c/Packages/TYPO3.CMS/+/94914
        // - 14.3: https://review.typo3.org/c/Packages/TYPO3.CMS/+/94916
        // - 13.4: https://review.typo3.org/c/Packages/TYPO3.CMS/+/94915
        //
        // @todo Raise the minimum TYPO3 core version constraint to the releases containing the fix
        //       above, once they are merged and released, and drop this note.
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

        // TYPO3 updates the reference index at the end of the outermost DataHandler only, with the updater it hands
        // to the DataHandlers it starts itself. The DataHandler started here runs within the command of the calling
        // one, it is never the outermost and would never write its updates. They are collected in an updater of its
        // own and written when the command is done. ReferenceIndexUpdater is @internal in TYPO3, only the argument of
        // DataHandler::start() is public API.
        //
        // It works with the backend user of the calling DataHandler and gets the settings of the calling one that
        // TYPO3 hands to the DataHandlers it starts itself (DataHandler::getLocalTCE()): an import stays an import
        // for the DataHandler hooks, disabled logging stays disabled, a bypassed record access check stays bypassed,
        // and its history records get the correlation id of the operation. The correlation id is set after start(),
        // which creates a new one. TYPO3 v14 hands it on to the DataHandlers this one starts. The settings that exist
        // in TYPO3 v13 only, `neverHideAtCopy`, `copyTree` and `bypassWorkspaceRestrictions`, are handed over by
        // NestedDataHandlerSettingsInterface.
        //
        // Not handed over: `dontProcessTransformations` applies to a datamap, this DataHandler processes a command
        // only, and the DataHandlers it starts to copy records set it themselves.
        //
        // An exception aborts the calling DataHandler as well, and TYPO3 drops its reference index updates then.
        // The updates collected here are dropped the same way, no try/finally.
        $referenceIndexUpdater = GeneralUtility::makeInstance(ReferenceIndexUpdater::class);
        $inlineDataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $inlineDataHandler->isImporting = $dataHandler->isImporting;
        $inlineDataHandler->enableLogging = $dataHandler->enableLogging;
        $inlineDataHandler->bypassAccessCheckForRecords = $dataHandler->bypassAccessCheckForRecords;
        $this->nestedDataHandlerSettings->handOver($dataHandler, $inlineDataHandler);
        $inlineDataHandler->start([], [
            $reference->parentTable => [
                $reference->parentUid => [
                    'inlineLocalizeSynchronize' => [
                        'field' => $reference->parentField,
                        'language' => $languageId,
                        'ids' => [$reference->childUid],
                    ],
                ],
            ],
        ], $dataHandler->BE_USER, $referenceIndexUpdater);
        $correlationId = $dataHandler->getCorrelationId();
        if ($correlationId !== null) {
            $inlineDataHandler->setCorrelationId($correlationId);
        }
        $inlineDataHandler->process_cmdmap();
        // Also when the command logged errors, the records it wrote stay written.
        $referenceIndexUpdater->update();
        $dataHandler->errorLog = array_merge($dataHandler->errorLog, $inlineDataHandler->errorLog);
    }

    /**
     * Reports a record whose inline relation could not be resolved reliably - an ambiguous relation
     * configuration or a missing parent record. Such a record is skipped instead of being localized
     * on its own, which would attach the translation to the wrong parent or to nothing at all. A
     * broken TCA or broken data is the likely cause, so the editor is informed.
     */
    private function logBrokenInlineRelation(
        InlineParentState $state,
        string $table,
        int $uid,
        DataHandler $dataHandler
    ): void {
        $message = match ($state) {
            InlineParentState::Ambiguous => 'DeepL translation of record "{table}:{uid}" has been skipped, because it'
                . ' matches more than one inline parent relation and cannot be attached reliably. Check the inline'
                . ' relation configuration (TCA) of this table.',
            InlineParentState::ParentMissing => 'DeepL translation of inline child record "{table}:{uid}" has been'
                . ' skipped, because the parent record it points to does not exist. Check the record\'s inline'
                . ' relation.',
            default => 'DeepL translation of record "{table}:{uid}" has been skipped.',
        };
        $messageData = [
            'table' => $table,
            'uid' => $uid,
        ];
        $dataHandler->log(
            $table,
            $uid,
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
    }
}
