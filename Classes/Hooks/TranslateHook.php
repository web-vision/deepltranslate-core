<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Hooks;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Contracts\Service\Attribute\Required;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use WebVision\Deepltranslate\Core\Domain\Enum\ContentFormat;
use WebVision\Deepltranslate\Core\Exception\InvalidArgumentException;
use WebVision\Deepltranslate\Core\Exception\LanguageIsoCodeNotFoundException;
use WebVision\Deepltranslate\Core\Exception\LanguageRecordNotFoundException;
use WebVision\Deepltranslate\Core\Service\ContentFormatResolver;

/**
 * The main translation rendering on localization.
 */
#[Autoconfigure(public: true)]
final class TranslateHook extends AbstractTranslateHook implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ContentFormatResolver $contentFormatResolver;

    /**
     * Setter injection to avoid changing the constructor signature within a maintenance branch.
     */
    #[Required]
    public function injectContentFormatResolver(ContentFormatResolver $contentFormatResolver): void
    {
        $this->contentFormatResolver = $contentFormatResolver;
    }

    /**
     * @param array{uid: int} $languageRecord
     */
    public function processTranslateTo_copyAction(
        string &$content,
        array $languageRecord,
        DataHandler $dataHandler,
        string $fieldName = ''
    ): void {
        if (MathUtility::canBeInterpretedAsInteger($content)) {
            return;
        }

        // Translation mode not set to DeepL translate skip the translation
        if ($this->processingInstruction->isDeeplMode() === false) {
            return;
        }

        // Table Information are important to find deepl configuration for site
        $tableName = $this->processingInstruction->getProcessingTable();
        if ($tableName === null) {
            return;
        }

        // Record Information are important to find deepl configuration for site
        $currentRecordId = $this->processingInstruction->getProcessingId();
        if ($currentRecordId === null) {
            return;
        }

        // `sys_file_metadata` translation needs additional care and is handled by private addon
        // "web-vision/deepltranslate-assets", opt out here for now based on that reasoning.
        if ($tableName === 'sys_file_metadata') {
            return;
        }

        $translatedContent = '';

        $currentRecord = BackendUtility::getRecord($tableName, $currentRecordId);
        if ($currentRecord === null) {
            return;
        }

        $currentRecordLanguage = 0;
        $pageId = $this->findCurrentParentPage($tableName, $currentRecord);
        try {
            $siteInformation = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId);
            if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['languageField'])) {
                $currentRecordLanguage = $currentRecord[$GLOBALS['TCA'][$tableName]['ctrl']['languageField']];
            }
        } catch (SiteNotFoundException $e) {
            $siteInformation = null;
        }

        if ($siteInformation === null) {
            return;
        }

        [$localizedTable, $localizedRecord] = $this->findLocalizedRecord($dataHandler)
            ?? $this->guardProcessingRecord($tableName, (int)$currentRecordId, $fieldName, $content, $dataHandler);
        $contentFormat = $localizedRecord !== null
            ? $this->contentFormatResolver->resolve($localizedTable, $fieldName, $localizedRecord)
            : ContentFormat::Unknown;
        if ($contentFormat === ContentFormat::Code) {
            return;
        }

        // Messages name the record the field belongs to, an inline child localized along with its parent included.
        $reportedTable = $localizedRecord !== null ? $localizedTable : $tableName;
        $reportedUid = $localizedRecord !== null ? (int)$localizedRecord['uid'] : (int)$currentRecordId;

        try {
            $sourceLanguageRecord = $this->languageService->getSourceLanguage($siteInformation, (int)$currentRecordLanguage);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException(
                sprintf(
                    'Source language not supported by DeepL. Possibly wrong Site configuration. Message: %s',
                    $e->getMessage(),
                ),
                1768994529,
                $e,
            );
        }
        try {
            $targetLanguageRecord = $this->languageService->getTargetLanguage($siteInformation, (int)$languageRecord['uid']);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException(
                sprintf(
                    'Target language not supported by DeepL. Possibly wrong Site configuration. Message: %s',
                    $e->getMessage(),
                ),
                1768994563,
                $e,
            );
        }
        try {
            $translatedContext = $this->createTranslateContextForRecords($content, $sourceLanguageRecord, $targetLanguageRecord);
            $translatedContext->setContentFormat($contentFormat);
            $translatedContent = $this->deeplService->translateContent($translatedContext);
            foreach ($translatedContext->getLostLinks() as $lostLink) {
                $this->flashMessages(
                    sprintf(
                        $this->translateLabel('translation.lostLink.message'),
                        $fieldName,
                        $reportedTable,
                        $reportedUid,
                        $lostLink->href,
                        $lostLink->text
                    ),
                    $this->translateLabel('translation.lostLink.title'),
                    ContextualFeedbackSeverity::WARNING,
                    true
                );
            }
            if ($translatedContent === '') {
                $this->logger?->warning(
                    'DeepL translation of field "{field}" of record {table}:{uid} failed, the field keeps the text of the source language.',
                    ['field' => $fieldName, 'table' => $reportedTable, 'uid' => $reportedUid]
                );
                // Stored in the session like the lost link message, so it outlives the AJAX request of the
                // localization wizard and the redirect after `tce_db`.
                $this->flashMessages(
                    sprintf($this->translateLabel('translation.failed.message'), $fieldName, $reportedTable, $reportedUid),
                    $this->translateLabel('translation.failed.title'),
                    ContextualFeedbackSeverity::WARNING,
                    true
                );
            }
        } catch (LanguageIsoCodeNotFoundException|LanguageRecordNotFoundException $e) {
            $this->flashMessages(
                $e->getMessage(),
                '',
                ContextualFeedbackSeverity::INFO
            );
        }

        if ($translatedContent !== '' && $content !== '') {
            $this->pageRepository->markPageAsTranslatedWithDeepl($pageId, (int)$languageRecord['uid']);
        }

        $content = $translatedContent !== '' ? $translatedContent : $content;
    }

    private function translateLabel(string $key): string
    {
        $label = 'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:' . $key;
        $languageService = $GLOBALS['LANG'] ?? null;
        if (!$languageService instanceof LanguageService) {
            $languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');
        }
        return $languageService->sL($label);
    }

    /**
     * Returns table and record of the field DataHandler passes. That is not the record of the processing
     * instruction for inline children localized along with it: `DataHandler::localize()` calls this hook for the
     * fields of every record it localizes, and `copyRecord_processRelation()` calls `localize()` for each child
     * while the parent is localized. The table and the uid are passed to the hook in no other way, so they are
     * read from the arguments of the calling `localize()`.
     *
     * This is safe: only the three innermost frames are read, and they are used only if this hook was called by
     * `localize()` of the very DataHandler instance passed to the hook. Any other caller gets null, and the record of
     * the processing instruction is used only if the field belongs to it. DataHandler reads its call stack the same
     * way to find its outermost instance, see `DataHandler::getOuterMostInstance()`.
     *
     * @todo Use the table and the uid passed by the core, once `DataHandler::localize()` passes them to
     *       `processTranslateTo_copyAction()`, and drop this.
     * @return array{0: string, 1: array<string, mixed>}|null null if this hook is not called by `localize()` of
     *         the given DataHandler
     */
    private function findLocalizedRecord(DataHandler $dataHandler): ?array
    {
        $frames = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
        $localizeFrame = $frames[2] ?? [];
        if (($frames[1]['function'] ?? '') !== 'processTranslateTo_copyAction'
            || ($localizeFrame['function'] ?? '') !== 'localize'
            || ($localizeFrame['object'] ?? null) !== $dataHandler
            || !is_string($localizeFrame['args'][0] ?? null)
            || !MathUtility::canBeInterpretedAsInteger($localizeFrame['args'][1] ?? null)
        ) {
            return null;
        }
        $table = $localizeFrame['args'][0];
        $record = $this->getRecordOfWorkspace($table, (int)$localizeFrame['args'][1], $dataHandler);
        return $record !== null ? [$table, $record] : null;
    }

    /**
     * Without the record `DataHandler::localize()` works on, the record of the processing instruction is only used
     * if the field belongs to it, otherwise a field of an inline child would be resolved in the parent's table.
     *
     * @return array{0: string, 1: array<string, mixed>|null}
     */
    private function guardProcessingRecord(
        string $processingTable,
        int $processingUid,
        string $fieldName,
        string $content,
        DataHandler $dataHandler
    ): array {
        $processingRecord = $this->getRecordOfWorkspace($processingTable, $processingUid, $dataHandler);
        $belongsToProcessingRecord = $processingRecord !== null
            && $fieldName !== ''
            && array_key_exists($fieldName, $processingRecord)
            && (string)$processingRecord[$fieldName] === $content;
        return [$processingTable, $belongsToProcessingRecord ? $processingRecord : null];
    }

    /**
     * The record as `DataHandler::localize()` reads it: in a workspace, with the values of its workspace version,
     * which are the values passed to the hook. A type changed in the workspace, a CType "bullets" changed to
     * "text" for example, changes the format of the field.
     *
     * @return array<string, mixed>|null
     */
    private function getRecordOfWorkspace(string $table, int $uid, DataHandler $dataHandler): ?array
    {
        $record = BackendUtility::getRecord($table, $uid);
        BackendUtility::workspaceOL($table, $record, (int)$dataHandler->BE_USER->workspace);
        return is_array($record) ? $record : null;
    }
}
