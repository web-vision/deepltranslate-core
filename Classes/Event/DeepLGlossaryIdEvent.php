<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Event;

use WebVision\Deepltranslate\Core\Domain\Dto\CurrentPage;

/**
 * Entry point for external synchronized DeepL glossaries.
 *
 * The event is fired right before the translation handling from the DeepL translation.
 * As entry, you get the source language, target language and the current page.
 *
 * Expects a string on parameter glossaryId. This ID is used for glossary-flavoured
 * translation. No check for synchronized glossary, the ID is taken "AS IS".
 */
final class DeepLGlossaryIdEvent
{
    public string $glossaryId = '';

    public function __construct(
        public readonly string $sourceLanguage,
        public readonly string $targetLanguage,
        public readonly ?CurrentPage $currentPage
    ) {}
}
