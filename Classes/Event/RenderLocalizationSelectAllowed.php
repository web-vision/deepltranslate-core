<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Event;

use Psr\Http\Message\RequestInterface;

/**
 * Event deciding if the localization dropdown should be rendered.
 * Could be used avoiding rendering for special cases, e.g., glossary or access denied.
 *
 * @deprecated Use page TS config options instead
 */
final class RenderLocalizationSelectAllowed
{
    public function __construct(
        public readonly RequestInterface $request,
        public bool $renderingAllowed = true
    ) {
    }
}
