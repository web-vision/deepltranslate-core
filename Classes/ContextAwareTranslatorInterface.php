<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core;

use DeepL\TextResult;
use WebVision\Deepltranslate\Core\Exception\ApiKeyNotSetException;

/**
 * A translator sending the context of a translation to DeepL, see
 * https://developers.deepl.com/docs/best-practices/working-with-context.
 *
 * {@see \WebVision\Deepltranslate\Core\Service\DeeplService} passes the context only to a translator implementing
 * this interface, so implementations of {@see TranslatorInterface} written before it keep working without one.
 */
interface ContextAwareTranslatorInterface extends TranslatorInterface
{
    /**
     * Dispatches a translation request towards the api.
     *
     * `$context` is text describing the content, which DeepL reads but does not translate, empty for none.
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
        string $formality = '',
        string $context = '',
    ): array|TextResult|null;
}
