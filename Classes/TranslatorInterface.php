<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core;

use DeepL\Language;
use DeepL\TextResult;
use WebVision\Deepltranslate\Core\Exception\ApiKeyNotSetException;

interface TranslatorInterface extends ClientInterface
{
    /**
     * Dispatches a translation request towards the api.
     *
     * @return TextResult|TextResult[]|null
     *
     * @throws ApiKeyNotSetException
     */
    public function translate(
        string $content,
        ?string $sourceLang,
        string $targetLang,
        string $glossary = '',
        string $formality = ''
    ): array|TextResult|null;

    /**
     * @return Language[]
     *
     * @throws ApiKeyNotSetException
     */
    public function getSupportedLanguageByType(string $type = 'target'): array;
}
