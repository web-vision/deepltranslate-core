<?php

declare(strict_types=1);

namespace WebVision\WvDeepltranslate;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\WvDeepltranslate\Domain\Dto\TranslateOptions;
use WebVision\WvDeepltranslate\Exception\ClientNotValidUrlException;

final class Client
{
    private const API_VERSION = 'v2';

    public const GLOSSARY_ENTRY_FORMAT = "%s\t%s\n";

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    public function __construct()
    {
        $this->configuration = GeneralUtility::makeInstance(Configuration::class);
        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
    }

    public function translate(
        string $content,
        TranslateOptions $options,
        string $glossary = ''
    ): ResponseInterface {
        $baseUrl = $this->buildBaseUrl('translate');

        $postFields = [
            'text' => $content,
        ];

        if (!empty($glossary)) {
            $postFields['glossary_id'] = $glossary;
        }

        $postFields['formality'] = $this->configuration->getFormality();

        $postFields = array_merge($postFields, $options->toArray());

        return $this->requestFactory->request($baseUrl, 'POST', $this->mergeRequiredRequestOptions([
            'form_params' => $postFields,
        ]));
    }

    public function getSupportedTargetLanguage(string $type = 'target'): ResponseInterface
    {
        $baseUrl = $this->buildBaseUrl('languages?type=' . $type);

        return $this->requestFactory->request($baseUrl, 'GET', $this->mergeRequiredRequestOptions());
    }

    public function getGlossaryLanguagePairs(): ResponseInterface
    {
        $baseUrl = $this->buildBaseUrl('glossary-language-pairs');

        return $this->requestFactory->request($baseUrl, 'GET', $this->mergeRequiredRequestOptions());
    }

    public function getAllGlossaries(): ResponseInterface
    {
        $baseUrl = $this->buildBaseUrl('glossaries');

        return $this->requestFactory->request($baseUrl, 'GET', $this->mergeRequiredRequestOptions());
    }

    public function getGlossary(string $glossaryId): ResponseInterface
    {
        $baseUrl = $this->buildBaseUrl(sprintf('glossaries/%s', $glossaryId));

        return $this->requestFactory->request($baseUrl, 'GET', $this->mergeRequiredRequestOptions());
    }

    public function createGlossary(
        string $glossaryName,
        string $sourceLang,
        string $targetLang,
        array $entries,
        string $entriesFormat = 'tsv'
    ): ResponseInterface {
        $baseUrl = $this->buildBaseUrl('glossaries');

        $postFields = [
            'name' => $glossaryName,
            'source_lang' => $sourceLang,
            'target_lang' => $targetLang,
            'entries_format' => $entriesFormat,
        ];

        $formatEntries = '';
        foreach ($entries as $entry) {
            /*
             * as the version without trimming in TCA is already published,
             * we trim a second time here
             * to avoid errors in DeepL client
             */
            $source = trim($entry['source']);
            $target = trim($entry['target']);
            if (empty($source) || empty($target)) {
                continue;
            }
            $formatEntries .= sprintf(self::GLOSSARY_ENTRY_FORMAT, $source, $target);
        }

        $postFields['entries'] = $formatEntries;

        return $this->requestFactory->request($baseUrl, 'POST', $this->mergeRequiredRequestOptions([
            'form_params' => $postFields,
        ]));
    }

    public function deleteGlossary(string $glossaryId): ResponseInterface
    {
        $baseUrl = $this->buildBaseUrl(sprintf('glossaries/%s', $glossaryId));

        return $this->requestFactory->request($baseUrl, 'DELETE', $this->mergeRequiredRequestOptions());
    }

    public function getGlossaryEntries(string $glossaryId): ResponseInterface
    {
        $baseUrl = $this->buildBaseUrl(sprintf('glossaries/%s/entries', $glossaryId));

        return $this->requestFactory->request($baseUrl, 'GET', $this->mergeRequiredRequestOptions());
    }

    private function buildBaseUrl(string $path): string
    {
        $url = sprintf(
            '%s://%s/%s/%s',
            $this->configuration->getApiScheme(),
            $this->configuration->getApiUrl(),
            self::API_VERSION,
            $path
        );

        if (!GeneralUtility::isValidUrl($url)) {
            throw new ClientNotValidUrlException(sprintf('BaseURL "%s" is not valid', $url), 1676125513);
        }

        return $url;
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function mergeRequiredRequestOptions(array $options = []): array
    {
        return array_merge_recursive(
            [
                'headers' => [
                    'Authorization' => sprintf('DeepL-Auth-Key %s', $this->configuration->getApiKey()),
                    'User-Agent' => 'TYPO3.WvDeepltranslate/1.0',
                ],
            ],
            $options
        );
    }
}
