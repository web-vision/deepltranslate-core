<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Client;

use DeepL\DeepLException;
use DeepL\DocumentHandle;
use DeepL\DocumentStatus;
use DeepL\GlossaryEntries;
use DeepL\GlossaryInfo;
use DeepL\GlossaryLanguagePair;
use DeepL\Language;
use DeepL\MultilingualGlossaryDictionaryEntries;
use DeepL\MultilingualGlossaryDictionaryInfo;
use DeepL\MultilingualGlossaryInfo;
use DeepL\RephraseTextResult;
use DeepL\StyleRuleInfo;
use DeepL\TextResult;
use DeepL\TranslatorOptions;
use DeepL\Usage;

/**
 * Interface mirroring the public API of DeepL\DeepLClient (deeplcom/deepl-php)
 * to be used for development and dependency inversion.
 *
 * Use {@see DeepLClientFactoryInterface} factory decoration chain to create
 * conrete clients, for example:
 *
 * ```
 * <?php
 *
 * namespace Vendor\Extension\Client;
 *
 * use WebVision\Deepltranslate\Core\Client\DeepLClientFactoryInterface;
 * use WebVision\Deepltranslate\Core\Client\DeepLClientInterface;
 *
 * final readonly SomeClient implements DeepLClientInterface {
 *
 *     private DeepLClientInterface $client;
 *
 *     public function __construct(
 *         private DeepLClientFactoryInterface $clientFactory,
 *     ) {
 *         $this->client = $clientFactory->create($this);
 *     }
 * }
 * ```
 */
interface DeepLClientInterface
{
    /**
     * @param array<TranslatorOptions::*|string, mixed> $options
     *
     * **Be aware** that interfaces should not define class constructor. This prevents class composition in case
     * two interfaces declares constructors in the interface. This interface surfes the purpose to detect if upstream
     * deepl php client changes the constructor on package updates and is therefore defined here as a maintenance
     * safe-guard.
     */
    public function __construct(
        string $apiKey,
        array $options = [],
    );

    /**
     * Translate text(s).
     *
     * @param string|string[] $texts
     * @param string|null $sourceLang e.g. 'EN', 'DE'
     * @param string $targetLang e.g. 'EN-GB', 'DE'
     * @param array{
     *     split_sentences?: 'on'|'off'|'default'|'nonewlines',
     *     preserve_formatting?: bool,
     *     formality?: 'default'|'more'|'less'|'prefer_more'|'prefer_less'|non-empty-string,
     *     context?: string,
     *     tag_handling?: 'xml'|'html',
     *     tag_handling_version?: 'v1'|'v2',
     *     outline_detection?: bool,
     *     splitting_tags?: string[],
     *     non_splitting_tags?: string[],
     *     ignore_tags?: string[],
     *     glossary?: non-empty-string|GlossaryInfo|MultilingualGlossaryInfo,
     *     model_type?: 'quality_optimized'|'prefer_quality_optimized'|'latency_optimized',
     *     extra_body_parameters?: array<string, string>,
     *     style_id?: string|StyleRuleInfo,
     *     custom_instructions?: string[]
     * } $options
     *
     * @return TextResult|TextResult[]
     *
     * @throws DeepLException
     */
    public function translateText(
        $texts,
        ?string $sourceLang,
        string $targetLang,
        array $options = []
    );

    /**
     * Rephrase/Improve writing for a given text.
     *
     * @param string|string[] $texts
     * @param string|null $targetLang
     * @param array{
     *     writing_style?: 'academic'|'business'|'casual'|'default'|'prefer_academic'|'prefer_business'|'prefer_casual'|'prefer_simple'|'simple'|string,
     *     tone?: 'confident'|'default'|'diplomatic'|'enthusiastic'|'friendly'|'prefer_confident'|'prefer_diplomatic'|'prefer_enthusiastic'|'prefer_friendly'|string
     * } $options
     *
     * @return RephraseTextResult|RephraseTextResult[]
     *
     * @throws DeepLException
     */
    public function rephraseText(
        $texts,
        ?string $targetLang = null,
        array $options = []
    );

    /**
     * List supported source languages.
     *
     * @return Language[]
     *
     * @throws DeepLException
     */
    public function getSourceLanguages(): array;

    /**
     * List supported target languages.
     *
     * @return Language[]
     *
     * @throws DeepLException
     */
    public function getTargetLanguages(): array;

    /**
     * Get usage information.
     *
     * @throws DeepLException
     */
    public function getUsage(): Usage;

    /**
     * Document translation: upload.
     *
     * @param string $inputFile
     * @param string|null $sourceLang
     * @param string $targetLang
     * @param array{
     *     formality?: 'default'|'more'|'less'|'prefer_more'|'prefer_less',
     *     glossary?: string|GlossaryInfo|MultilingualGlossaryInfo,
     *     extra_body_parameters?: array<string, string>,
     *     enable_document_minification?: bool
     * } $options
     *
     * @throws DeepLException
     */
    public function uploadDocument(
        string $inputFile,
        ?string $sourceLang,
        string $targetLang,
        array $options = []
    ): DocumentHandle;

    /**
     * Document translation: wait until completed.
     *
     * @throws DeepLException
     */
    public function waitUntilDocumentTranslationComplete(DocumentHandle $handle): DocumentStatus;

    /**
     * Document translation: upload, wait and download.
     *
     * @param string $inputFile
     * @param string $outputFile
     * @param string|null $sourceLang
     * @param string $targetLang
     * @param array{
     *     formality?: 'default'|'more'|'less'|'prefer_more'|'prefer_less',
     *     glossary?: string|GlossaryInfo|MultilingualGlossaryInfo,
     *     extra_body_parameters?: array<string, string>,
     *     enable_document_minification?: bool
     * } $options
     *
     * @throws DeepLException
     */
    public function translateDocument(
        string $inputFile,
        string $outputFile,
        ?string $sourceLang,
        string $targetLang,
        array $options = []
    ): DocumentStatus;

    /**
     * Document translation: check status.
     *
     * @throws DeepLException
     */
    public function getDocumentStatus(DocumentHandle $handle): DocumentStatus;

    /**
     * Document translation: download result.
     *
     * @throws DeepLException
     */
    public function downloadDocument(DocumentHandle $handle, string $outputFile): void;

    /**
     * DEPRECATED single-language glossary APIs (still exposed for BC).
     *
     * @return GlossaryLanguagePair[]
     *
     * @throws DeepLException
     */
    public function getGlossaryLanguages(): array;

    /**
     * @return GlossaryInfo[]
     *
     * @throws DeepLException
     */
    public function listGlossaries(): array;

    /**
     * @throws DeepLException
     */
    public function getGlossary(string $glossaryId): GlossaryInfo;

    /**
     * @throws DeepLException
     */
    public function createGlossary(
        string $name,
        string $sourceLang,
        string $targetLang,
        GlossaryEntries $entries
    ): GlossaryInfo;

    /**
     * @throws DeepLException
     */
    public function createGlossaryFromCsv(
        string $name,
        string $sourceLang,
        string $targetLang,
        string $csvContent
    ): GlossaryInfo;

    /**
     * @throws DeepLException
     */
    public function deleteGlossary(string $glossary): void;

    /**
     * @throws DeepLException
     */
    public function getGlossaryEntries(string $glossary): GlossaryEntries;

    /**
     * Multilingual glossary APIs.
     *
     * @param MultilingualGlossaryDictionaryEntries[] $dictionaries
     *
     * @throws DeepLException
     */
    public function createMultilingualGlossary(string $name, array $dictionaries): MultilingualGlossaryInfo;

    /**
     * @throws DeepLException
     */
    public function createMultilingualGlossaryFromCsv(
        string $name,
        string $sourceLang,
        string $targetLang,
        string $csvContent
    ): MultilingualGlossaryInfo;

    /**
     * @param MultilingualGlossaryDictionaryEntries[]|null $dictionaries
     *
     * @throws DeepLException
     */
    public function updateMultilingualGlossary(
        string $glossary,
        ?string $newName,
        ?array $dictionaries
    ): MultilingualGlossaryInfo;

    /**
     * @throws DeepLException
     */
    public function replaceMultilingualGlossaryDictionary(
        string $glossary,
        MultilingualGlossaryDictionaryEntries $dictionaries
    ): MultilingualGlossaryDictionaryInfo;

    /**
     * @throws DeepLException
     */
    public function getMultilingualGlossary(string $glossary): MultilingualGlossaryInfo;

    /**
     * @throws DeepLException
     */
    public function deleteMultilingualGlossary(string $glossary): void;

    /**
     * @return MultilingualGlossaryInfo[]
     *
     * @throws DeepLException
     */
    public function listMultilingualGlossaries(): array;

    /**
     * @return array<string|int, mixed>
     * @throws DeepLException
     */
    public function getMultilingualGlossaryEntries(string $glossary, string $sourceLang, string $targetLang): array;

    /**
     * @throws DeepLException
     */
    public function deleteMultilingualGlossaryDictionary(
        string $glossary,
        ?MultilingualGlossaryDictionaryInfo $dictionary,
        ?string $sourceLang,
        ?string $targetLang
    ): void;

    /**
     * @return array<string, mixed>
     *
     * @throws DeepLException
     */
    public function getAllStyleRules(?int $page = null, ?int $pageSize = null, ?bool $detailed = null): array;
}
