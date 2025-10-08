<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use DeepL\Language;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use WebVision\Deepltranslate\Core\Domain\Dto\TranslateContext;
use WebVision\Deepltranslate\Core\Event\DeepLGlossaryIdEvent;
use WebVision\Deepltranslate\Core\Exception\ApiKeyNotSetException;
use WebVision\Deepltranslate\Core\TranslatorInterface;
use WebVision\Deepltranslate\Core\Utility\DeeplBackendUtility;

/**
 * Main entry point for connecting TYPO3 backend to the DeepL API
 */
#[Autoconfigure(public: true)]
final class DeeplService
{
    private FrontendInterface $cache;

    private TranslatorInterface $client;
    private ProcessingInstruction $processingInstruction;

    public function __construct(
        #[Autowire(service: 'cache.deepltranslateCore')]
        FrontendInterface $cache,
        TranslatorInterface $client,
        ProcessingInstruction $processingInstruction,
        private readonly EventDispatcher $eventDispatcher,
        private readonly LoggerInterface $logger
    ) {
        $this->cache = $cache;
        $this->client = $client;
        $this->processingInstruction = $processingInstruction;
    }

    /**
     * DeepL Api Call and format text to use in TYPO3
     * This function does not support formality languages please use DeeplService::translateContent()
     *
     * @deprecated Please use this function @see DeeplService::translateContent()
     */
    public function translateRequest(
        string $content,
        string $targetLanguage,
        string $sourceLanguage
    ): string {
        $translateContext = new TranslateContext($content);
        $translateContext->setSourceLanguageCode($sourceLanguage);
        $translateContext->setTargetLanguageCode($targetLanguage);

        return $this->translateContent($translateContext);
    }

    /**
     * Deepl Api Call and formart text to use in TYPO3
     */
    public function translateContent(TranslateContext $translateContext): string
    {
        if ($this->processingInstruction->isDeeplMode() === false) {
            $this->logger->warning('DeepL mode not set. Exit.');
            return $translateContext->getContent();
        }
        // If the source language is set to Autodetect, no glossary can be detected.
        if ($translateContext->getSourceLanguageCode() !== null) {
            $glossaryEvent = $this->eventDispatcher->dispatch(new DeepLGlossaryIdEvent(
                $translateContext->getSourceLanguageCode(),
                $translateContext->getTargetLanguageCode(),
                DeeplBackendUtility::detectCurrentPage($this->processingInstruction)
            ));
            $glossaryId = $glossaryEvent->glossaryId;
            if ($glossaryId !== '') {
                $translateContext->setGlossaryId($glossaryId);
            }
        }

        try {
            $response = $this->client->translate(
                $translateContext->getContent(),
                $translateContext->getSourceLanguageCode(),
                $translateContext->getTargetLanguageCode(),
                $translateContext->getGlossaryId(),
                $translateContext->getFormality()
            );
        } catch (ApiKeyNotSetException $exception) {
            // @todo Add proper error logging here.
            return $translateContext->getContent();
        }

        if ($response === null) {
            $this->logger->warning('Translation not successful');

            return '';
        }

        if (is_array($response)) {
            $content = '';
            foreach ($response as $result) {
                $content .= $result->text;
            }
        } else {
            $content = $response->text;
        }

        return htmlspecialchars_decode($content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
    }

    /**
     * ToDo: Maybe rename the function to "findSupportedTargetLanguage".
     */
    public function detectTargetLanguage(string $languageCode): ?Language
    {
        return $this->findSupportedLanguages(
            $this->getSupportLanguage()['target'],
            $languageCode
        );
    }

    public function isTargetLanguageSupported(string $languageCode): bool
    {
        $supportedTargetLanguage = $this->getSupportLanguage()['target'];
        $language = $this->findSupportedLanguages($supportedTargetLanguage, $languageCode);
        return $language !== null;
    }

    /**
     * ToDo: Maybe rename the function to "findSupportedSourceLanguage".
     */
    public function detectSourceLanguage(string $languageCode): ?Language
    {
        return $this->findSupportedLanguages(
            $this->getSupportLanguage()['source'],
            $languageCode
        );
    }

    public function isSourceLanguageSupported(string $languageCode): bool
    {
        $supportedTargetLanguage = $this->getSupportLanguage()['source'];
        $language = $this->findSupportedLanguages($supportedTargetLanguage, $languageCode);
        return $language !== null;
    }

    /**
     * @param Language[] $langauges
     *
     * @return Language|null
     */
    private function findSupportedLanguages(array $langauges, string $languageCode): ?Language
    {
        foreach ($langauges as $supportedLanguage) {
            if ($supportedLanguage->code === $languageCode) {
                return $supportedLanguage;
            }
        }

        return null;
    }

    public function hasLanguageFormalitySupport(string $languageCode): bool
    {
        $languages = array_filter(
            $this->getSupportLanguage()['target'],
            function (Language $targetLanguage) use ($languageCode) {
                return $targetLanguage->code === $languageCode;
            }
        );
        /** @var Language $language */
        $language = array_shift($languages);

        return $language->supportsFormality !== null ? $language->supportsFormality : false;
    }

    /**
     * Default supported languages
     *
     * @see https://www.deepl.com/de/docs-api/translating-text/#request
     * @return array{source: Language[], target: Language[]}
     */
    public function getSupportLanguage(): array
    {
        return $this->loadSupportedLanguages();
    }

    /**
     * ToDo: Build own deepl language support object
     *
     * @return array{source: Language[], target: Language[]}
     */
    private function loadSupportedLanguages(): array
    {
        $apiSupportedLanguages = [
            'source' => [],
            'target' => [],
        ];

        $cacheIdentifier = 'wv-deepl-supported-languages-target';
        if (($supportedTargetLanguages = $this->cache->get($cacheIdentifier)) === false) {
            $supportedTargetLanguages = $this->loadSupportedLanguagesFromAPI();

            $this->cache->set($cacheIdentifier, $supportedTargetLanguages, [], 86400);
        }

        $apiSupportedLanguages['target'] = $supportedTargetLanguages;

        $cacheIdentifier = 'wv-deepl-supported-languages-source';

        if (($supportedSourceLanguages = $this->cache->get($cacheIdentifier)) === false) {
            $supportedSourceLanguages = $this->loadSupportedLanguagesFromAPI('source');

            $this->cache->set($cacheIdentifier, $supportedSourceLanguages, [], 86400);
        }

        $apiSupportedLanguages['source'] = $supportedSourceLanguages;

        return $apiSupportedLanguages;
    }

    /**
     * @return Language[]
     */
    private function loadSupportedLanguagesFromAPI(string $type = 'target'): array
    {
        try {
            return $this->client->getSupportedLanguageByType($type);
        } catch (ApiKeyNotSetException $exception) {
            $this->logger->error(sprintf('%s (%d)', $exception->getMessage(), $exception->getCode()));
            return [];
        }
    }
}
