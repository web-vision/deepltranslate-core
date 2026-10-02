<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

/**
 * A link of the source content that the translation does not contain any more, see
 * {@see HtmlXmlConverterInterface::xmlToHtml()}.
 */
final class LostLink
{
    public function __construct(
        public readonly string $href,
        public readonly string $text,
    ) {
    }
}
