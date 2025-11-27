<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Hooks;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use WebVision\Deepltranslate\Core\Exception\InvalidArgumentException;
use WebVision\Deepltranslate\Core\Exception\LanguageIsoCodeNotFoundException;
use WebVision\Deepltranslate\Core\Exception\LanguageRecordNotFoundException;

/**
 * The main translation rendering on localization.
 */
#[Autoconfigure(public: true)]
final class TranslateHook extends AbstractTranslateHook
{
    /**
     * @param array{uid: int} $languageRecord
     */
    public function processTranslateTo_copyAction(
        string &$content,
        array $languageRecord,
        DataHandler $dataHandler
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
            $translatedContent = $this->deeplService->translateContent($translatedContext);
            if ($translatedContent === '') {
                $this->flashMessages(
                    'Translation not successful', // @todo Use locallang label
                    '',
                    ContextualFeedbackSeverity::INFO
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
}
