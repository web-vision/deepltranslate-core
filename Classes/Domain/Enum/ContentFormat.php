<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Domain\Enum;

/**
 * Format of the content in a {@see \WebVision\Deepltranslate\Core\Domain\Dto\TranslateContext}. It decides how
 * the content is prepared for DeepL and how the result is returned.
 */
enum ContentFormat
{
    /**
     * HTML of a rich text field. Returned as HTML, entities stay as the rich text editor stores them.
     */
    case RichText;

    /**
     * Text of a field without rich text editor, for example an input. Returned as literal text, so "<" or
     * "&" in the text never turn into markup or entities.
     */
    case PlainText;

    /**
     * Source code other than HTML, for example TypoScript in a code editor. It is not sent to DeepL and returned
     * unchanged. HTML in a code editor, like the content element "Plain HTML", is rich text.
     */
    case Code;

    /**
     * The caller did not tell. Content without a tag, for example "Fish & Chips", and content the HTML parser would
     * change, for example "Ref <title> & co" or "a<b", is handled as plain text, any other content as rich text, see
     * {@see \WebVision\Deepltranslate\Core\Service\PlainTextDetector}. A caller that knows the field sets its
     * format, a plain text field holding markup the parser keeps would be returned as rich text.
     */
    case Unknown;
}
