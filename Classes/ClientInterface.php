<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core;

use DeepL\DeepLClient;
use DeepL\GlossaryEntries;
use DeepL\GlossaryInfo;
use DeepL\GlossaryLanguagePair;
use WebVision\Deepltranslate\Core\Exception\ApiKeyNotSetException;

/**
 * Interface for custom client implementation and which methods are expected.
 *
 * @internal use only for testing, not part of public extension API.
 * @property DeepLClient $translator
 */
interface ClientInterface
{

    /**
     * @return GlossaryLanguagePair[]
     *
     * @throws ApiKeyNotSetException
     */
    public function getGlossaryLanguagePairs(): array;

    /**
     * @return GlossaryInfo[]
     *
     * @throws ApiKeyNotSetException
     * @deprecated This function is deprecated in favour of multilingual glossaries and will be removed in future versions
     */
    public function getAllGlossaries(): array;

    /**
     * @throws ApiKeyNotSetException
     * @deprecated This function is deprecated in favour of multilingual glossaries and will be removed in future versions
     */
    public function getGlossary(string $glossaryId): ?GlossaryInfo;

    /**
     * @param array<int, array{source: string, target: string}> $entries
     *
     * @throws ApiKeyNotSetException
     * @deprecated This function is deprecated in favour of multilingual glossaries and will be removed in future versions
     */
    public function createGlossary(
        string $glossaryName,
        string $sourceLang,
        string $targetLang,
        array $entries
    ): GlossaryInfo;

    /**
     * @throws ApiKeyNotSetException
     * @deprecated This function is deprecated in favour of multilingual glossaries and will be removed in future versions
     */
    public function deleteGlossary(string $glossaryId): void;

    /**
     * @throws ApiKeyNotSetException
     * @deprecated This function is deprecated in favour of multilingual glossaries and will be removed in future versions
     */
    public function getGlossaryEntries(string $glossaryId): ?GlossaryEntries;
}
