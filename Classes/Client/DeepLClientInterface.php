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
use DeepL\TextResult;
use DeepL\Usage;

/**
 * Interface mirroring the public API of DeepL\Translator (deeplcom/deepl-php v1.12.0)
 * to be used for development and dependency inversion.
 */
interface DeepLClientInterface
{
    /**
     * Translate text(s).
     *
     * @param string|string[] $text
     * @param string|null $sourceLang e.g. 'EN', 'DE'
     * @param string $targetLang e.g. 'EN-GB', 'DE'
     * @param array{
     *     glossary_id?: string,
     *     formality?: 'default'|'more'|'less'|'prefer_more'|'prefer_less',
     *     tag_handling?: 'xml'|'html',
     *     outline_detection?: bool,
     *     non_splitting_tags?: string[],
     *     splitting_tags?: string[],
     *     ignore_tags?: string[],
     *     preserve_formatting?: bool,
     *     context?: string
     * } $options
     *
     * @return TextResult|TextResult[]
     *
     * @throws DeepLException
     */
    public function translateText(
        string|array $text,
        ?string $sourceLang,
        string $targetLang,
        array $options = []
    ): TextResult|array;

    /**
     * Rephrase/Improve writing for a given text.
     *
     * @param array{
     *     writing_style?: string,
     *     tone?: string
     * } $options
     *
     * @throws DeepLException
     */
    public function rephraseText(
        string $text,
        ?string $targetLanguage = null,
        array $options = []
    ): string;

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
     * @param resource|string $file
     * @param string|null $filename
     * @param array{
     *     formality?: 'default'|'more'|'less'|'prefer_more'|'prefer_less',
     *     glossary_id?: string
     * } $options
     *
     * @throws DeepLException
     */
    public function translateDocument(
        $file,
        ?string $filename,
        ?string $sourceLang,
        string $targetLang,
        array $options = []
    ): DocumentHandle;

    /**
     * Document translation: check status.
     *
     * @throws DeepLException
     */
    public function getDocumentStatus(DocumentHandle $handle): DocumentStatus;

    /**
     * Document translation: download result to a file path or stream resource.
     *
     * @param resource|string $destination
     *
     * @throws DeepLException
     */
    public function downloadDocument(DocumentHandle $handle, $destination): void;

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
    public function deleteGlossary(string $glossaryId): void;

    /**
     * @throws DeepLException
     */
    public function getGlossaryEntries(string $glossaryId): GlossaryEntries;

    /**
     * Multilingual glossary APIs.
     *
     * @param MultilingualGlossaryDictionaryEntries[] $dictionaries
     *
     * @throws DeepLException
     */
    public function createMultilingualGlossary(string $name, array $dictionaries = []): MultilingualGlossaryInfo;

    /**
     * @param MultilingualGlossaryDictionaryEntries[] $dictionaries
     *
     * @throws DeepLException
     */
    public function updateMultilingualGlossary(
        string $glossaryId,
        ?string $newName,
        array $dictionaries
    ): MultilingualGlossaryInfo;

    /**
     * @throws DeepLException
     */
    public function replaceMultilingualGlossaryDictionary(
        string $glossaryId,
        MultilingualGlossaryDictionaryEntries $dictionaries
    ): MultilingualGlossaryDictionaryInfo;

    /**
     * @throws DeepLException
     */
    public function deleteMultilingualGlossary(string $glossaryId): void;

    /**
     * @return MultilingualGlossaryInfo[]
     *
     * @throws DeepLException
     */
    public function listMultilingualGlossaries(): array;

    /**
     * @throws DeepLException
     */
    public function deleteMultilingualGlossaryDictionary(
        string $glossaryId,
        ?string $language = null,
        ?string $sourceLanguage = null,
        ?string $targetLanguage = null
    ): void;
}
