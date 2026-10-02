<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

/**
 * The translation as HTML and the links of the source it lost, see {@see HtmlXmlConverterInterface::xmlToHtml()}.
 */
final readonly class ConvertedHtml
{
    /**
     * @param list<LostLink> $lostLinks
     */
    public function __construct(
        public string $html,
        public array $lostLinks = [],
    ) {}
}
