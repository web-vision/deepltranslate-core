<?php

declare(strict_types=1);

namespace WebVision\WvDeepltranslate\Hooks;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\WvDeepltranslate\Domain\Dto\TranslateOptions;
use WebVision\WvDeepltranslate\Domain\Repository\PageRepository;
use WebVision\WvDeepltranslate\Exception\LanguageIsoCodeNotFoundException;
use WebVision\WvDeepltranslate\Exception\LanguageRecordNotFoundException;
use WebVision\WvDeepltranslate\Resolver\RichtextAllowTagsResolver;
use WebVision\WvDeepltranslate\Service\DeeplService;
use WebVision\WvDeepltranslate\Service\GoogleTranslateService;
use WebVision\WvDeepltranslate\Service\LanguageService;
use WebVision\WvDeepltranslate\Utility\HtmlUtility;

class TranslateHook
{
    protected DeeplService $deeplService;

    protected GoogleTranslateService $googleService;

    protected PageRepository $pageRepository;

    private LanguageService $languageService;

    public function __construct(
        ?PageRepository $pageRepository = null,
        ?DeeplService $deeplService = null,
        ?GoogleTranslateService $googleService = null,
        ?LanguageService $languageService = null
    ) {
        $this->deeplService = $deeplService ?? GeneralUtility::makeInstance(DeeplService::class);
        $this->pageRepository = $pageRepository ?? GeneralUtility::makeInstance(PageRepository::class);
        $this->languageService = $languageService ?? GeneralUtility::makeInstance(LanguageService::class);
        $this->googleService = $googleService ?? GeneralUtility::makeInstance(GoogleTranslateService::class);
    }

    /**
     * @param array{uid: int} $languageRecord
     */
    public function processTranslateTo_copyAction(
        string &$content,
        array $languageRecord,
        DataHandler $dataHandler,
        string $columnName
    ): string {
        $tableName = '';
        $currentRecordId = '';

        $cmdmap = $dataHandler->cmdmap;
        foreach ($cmdmap as $key => $array) {
            $tableName = $key;
            foreach ($array as $innerkey => $innervalue) {
                $currentRecordId = $innerkey;
                break;
            }
            break;
        }

        $customMode = $cmdmap['localization']['custom']['mode'] ?? null;

        //translation mode set to deepl or google translate
        if ($customMode === null) {
            return $content;
        }

        $siteInformation = $this->languageService->getCurrentSite($tableName, $currentRecordId);

        $translatedContent = '';
        $targetLanguageRecord = [];

        $translateOptions = GeneralUtility::makeInstance(TranslateOptions::class);
        $richtextAllowTagsResolver = GeneralUtility::makeInstance(RichtextAllowTagsResolver::class);
        $translateOptions->setSplittingTags(
            $richtextAllowTagsResolver->resolve($tableName, $currentRecordId, $columnName)
        );

        try {
            $sourceLanguageRecord = $this->languageService->getSourceLanguage(
                $siteInformation['site']
            );
            $translateOptions->setSourceLanguage($sourceLanguageRecord['language_isocode']);

            $targetLanguageRecord = $this->languageService->getTargetLanguage(
                $siteInformation['site'],
                (int)$languageRecord['uid']
            );
            $translateOptions->setTargetLanguage($targetLanguageRecord['language_isocode']);

            $translatedContent = $this->translateContent(
                $content,
                $translateOptions,
                $customMode,
            );
        } catch (LanguageIsoCodeNotFoundException|LanguageRecordNotFoundException $e) {
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $e->getMessage(),
                '',
                FlashMessage::INFO
            );
            GeneralUtility::makeInstance(FlashMessageService::class)
                ->getMessageQueueByIdentifier()
                ->addMessage($flashMessage);
        }

        if ($translatedContent !== '') {
            if ($content !== '' && $customMode === 'deepl' && !empty($targetLanguageRecord)) {
                if (isset($siteInformation['pageUid'])) {
                    $this->pageRepository->markPageAsTranslatedWithDeepl($siteInformation['pageUid'], $targetLanguageRecord);
                }
            }

            // only the parameter reference is in use for content translate
            $content = $translatedContent;
        }

        return $content;
    }

    /**
     * These logics were outsourced to test them and later to resolve them in a service
     */
    public function translateContent(
        string $content,
        TranslateOptions $translateOptions,
        string $customMode
    ): string {
        // mode deepl
        if ($customMode == 'deepl') {
            $response = $this->deeplService->translateRequest(
                $content,
                $translateOptions
            );

            if (!empty($response) && isset($response['translations'])) {
                foreach ($response['translations'] as $translation) {
                    if ($translation['text'] != '') {
                        $content = htmlspecialchars_decode($translation['text'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
                        break;
                    }
                }
            }
        } //mode google
        elseif ($customMode == 'google') {
            $response = $this->googleService->translate(
                $translateOptions->getSourceLanguage(),
                $translateOptions->getTargetLanguage(),
                $content
            );

            if (!empty($response)) {
                if (HtmlUtility::isHtml($response)) {
                    $content = preg_replace('/\/\s/', '/', $response);
                    $content = preg_replace('/\>\s+/', '>', $content);
                }
            }
        }

        return $content;
    }
}
