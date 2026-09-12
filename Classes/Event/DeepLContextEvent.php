<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Event;

use WebVision\Deepltranslate\Core\Domain\Dto\CurrentPage;

/**
 * Entry point to enrich or override the automatically resolved translation context.
 *
 * The event is fired right before the translation handling from the DeepL translation.
 * As entry, you get the determined context, source language, target language and the
 * current page.
 *
 * The context parameter provides additional text that influences a translation but is
 * not itself translated. Setting the context to an empty string disables the context
 * for the current translation request; otherwise the value is passed to the DeepL API.
 */
final class DeepLContextEvent
{
    public function __construct(
        public string $context,
        public readonly string $sourceLanguage,
        public readonly string $targetLanguage,
        public readonly ?CurrentPage $currentPage
    ) {}
}