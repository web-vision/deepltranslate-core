<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Event;

use Psr\Http\Message\RequestInterface;

/**
 * Event deciding if the localization dropdown should be rendered.
 * Could be used avoiding rendering for special cases, e.g., glossary or access denied.
 *
 * @depreacted used only for TYPO3 v13 compatibility and will not be dispatched for TYPO3 v14
 *             and should be resort to TYPO3 v14 localization handler feature provided by the
 *             TYPO3 Core.
 */
final class RenderLocalizationSelectAllowed
{
    public function __construct(
        public readonly RequestInterface $request,
        public bool $renderingAllowed = true
    ) {}
}
