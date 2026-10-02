<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Event;

use WebVision\Deepltranslate\Core\Domain\Dto\CurrentPage;

/**
 * Dispatched right before a field is sent to DeepL, to change or remove the context of the request.
 *
 * The context is text describing the content, which DeepL reads to translate it but does not translate itself, and
 * whose characters are not billed. `$context` holds the context the request would be sent with: the one set by the
 * caller, else the context of the page, else the context of the site. An empty string sends the request without a
 * context.
 *
 * `$sourceLanguage` is null when DeepL detects the source language. `$currentPage` is the page of the translated
 * record, null if it has none. Unlike {@see DeepLGlossaryIdEvent}, it is never the page of a record translated before
 * in the same request.
 */
final class DeepLContextEvent
{
    public function __construct(
        public string $context,
        public readonly ?string $sourceLanguage,
        public readonly string $targetLanguage,
        public readonly ?CurrentPage $currentPage,
    ) {}
}
