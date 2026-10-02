<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use WebVision\Deepltranslate\Core\Exception\XmlConversionException;

/**
 * Converts field content between the HTML stored by TYPO3 and the well-formed XML sent to DeepL with
 * `tag_handling=xml`. The content is always read as HTML, so escape plain text before, see
 * {@see \WebVision\Deepltranslate\Core\Service\DeeplService::translateContent()}.
 */
interface HtmlXmlConverterInterface
{
    /**
     * Returns the content as well-formed XML fragment prepared for DeepL, for example `<br>` becomes `<br/>`
     * and `&nbsp;` becomes the non-breaking space character. What XML 1.0 does not allow is removed.
     */
    public function htmlToXml(string $html): string;

    /**
     * Returns the tags the XML of {@see self::htmlToXml()} needs in the DeepL options for this content, in
     * addition to the tags the translator sends anyway.
     *
     * @return array{splitting_tags: list<string>, non_splitting_tags: list<string>}
     */
    public function getTagHandlingOptions(string $html): array;

    /**
     * Returns the XML fragment, usually the DeepL result, serialized as HTML5 again, with the preparation of
     * {@see self::htmlToXml()} reverted, and the links of the source the translation lost. `$sourceHtml` must be
     * the content that was passed to {@see self::htmlToXml()}, the preparation is derived from it again.
     *
     * @throws XmlConversionException if the fragment is not well-formed XML or does not belong to `$sourceHtml`
     */
    public function xmlToHtml(string $xml, string $sourceHtml): ConvertedHtml;
}
