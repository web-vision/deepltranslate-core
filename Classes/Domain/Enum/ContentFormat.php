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
     * The caller did not tell. The content is handled as HTML and HTML special characters are decoded in
     * the result, as before this format existed.
     */
    case Unknown;
}
