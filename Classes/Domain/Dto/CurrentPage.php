<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Domain\Dto;

use WebVision\Deepltranslate\Core\Event\DeepLGlossaryIdEvent;

/**
 * This class holds the current working page
 * while processing inside the DataHandler
 *
 * It is the main entry point for extensions during events
 * detecting the right page,
 * for example, while detecting a glossary.
 *
 * @see DeepLGlossaryIdEvent for an usage example
 */
final class CurrentPage
{
    public function __construct(
        public readonly int $uid,
        public readonly string $title
    ) {
    }
}
