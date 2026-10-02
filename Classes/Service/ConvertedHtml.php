<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

/**
 * The translation as HTML and the links of the source it lost, see {@see HtmlXmlConverterInterface::xmlToHtml()}.
 */
final class ConvertedHtml
{
    /**
     * @param list<LostLink> $lostLinks
     */
    public function __construct(
        public readonly string $html,
        public readonly array $lostLinks = [],
    ) {
    }
}
