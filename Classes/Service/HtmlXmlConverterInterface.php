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
     * Returns the content as well-formed XML fragment, for example `<br>` becomes `<br/>` and `&nbsp;`
     * becomes the non-breaking space character.
     */
    public function htmlToXml(string $html): string;

    /**
     * Returns the XML fragment, usually the DeepL result, serialized as HTML5 again.
     *
     * @throws XmlConversionException if the fragment is not well-formed XML
     */
    public function xmlToHtml(string $xml): string;
}
