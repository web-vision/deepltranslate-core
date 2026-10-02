<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service\XmlPreparation;

/**
 * The names the preparation steps send to DeepL and the word checks they share.
 *
 * DeepL places tags on words: tags stay attached to the words they wrap, and placeholder tags are placed next
 * to the translation of the words before or after them (https://developers.deepl.com/docs/translate/translating-xml).
 * An element boundary inside a word has no place to go, which is what most preparation steps work around.
 *
 * The helper names are short on purpose: DeepL limits the total request size to 128 KiB
 * (https://developers.deepl.com/docs/resources/usage-limits), and every inline element carries a reference
 * number. Tags are not billed, but they count for that limit.
 *
 * @internal
 */
final class InlineMarkup
{
    /**
     * Inline elements of rich text. DeepL treats every element holding text as a sentence boundary unless it is
     * listed in `non_splitting_tags`, which cut the sentence of issue #489 into fragments and dropped its main
     * clause. Listing these elements there keeps a sentence together across its inline markup.
     */
    public const ELEMENTS = [
        'a', 'abbr', 'b', 'bdi', 'bdo', 'cite', 'code', 'data', 'del', 'dfn', 'em', 'i', 'ins', 'kbd', 'mark', 'q',
        's', 'samp', 'small', 'span', 'strong', 'sub', 'sup', 'time', 'u', 'var',
    ];

    /**
     * Every element and attribute the preparation sends starts with it. Content that uses such a name itself is
     * sent without preparation, see {@see \WebVision\Deepltranslate\Core\Service\HtmlXmlConverter}.
     */
    public const HELPER_PREFIX = 'dlt-';

    /**
     * The reference number of an inline element, see {@see ReferenceStep}.
     */
    public const REFERENCE_ATTRIBUTE = 'dlt-r';

    /**
     * The number of the attribute text of the source of an element whose attributes the parser or the serializer
     * would change, see {@see SourceTags}.
     */
    public const SOURCE_ATTRIBUTES_ATTRIBUTE = 'dlt-a';

    /**
     * Sent instead of inline elements that touch each other within a word, listed in `splitting_tags`, see
     * {@see TouchingElementStep}.
     */
    public const SPLITTING_ELEMENT = 'dlt-s';

    /**
     * Sent instead of a `<sup>` or `<sub>` holding a symbol, listed in `non_splitting_tags`, see
     * {@see ScriptPlaceholderStep}.
     */
    public const PLACEHOLDER_ELEMENT = 'dlt-p';

    /**
     * A character that belongs to a word: letter, digit, combining mark or symbol, like `®` or `€`.
     */
    public const WORD_CHARACTER = '[\p{L}\p{N}\p{M}\p{S}]';

    private function __construct()
    {
    }

    public static function isInlineElement(?\DOMNode $node): bool
    {
        return $node instanceof \DOMElement && in_array($node->localName, self::ELEMENTS, true);
    }

    /**
     * A cheap check before parsing: false if no element or attribute of the content can start with
     * {@see self::HELPER_PREFIX}. It ignores the case, the HTML parser lowercases the names, `DLT-R` is `dlt-r` in
     * the parsed content.
     */
    public static function mayUseHelperNames(string $html): bool
    {
        return stripos($html, self::HELPER_PREFIX) !== false;
    }

    public static function referenceOf(\DOMElement $element): ?int
    {
        $reference = $element->getAttribute(self::REFERENCE_ATTRIBUTE);
        return preg_match('/^[0-9]+$/', $reference) === 1 ? (int)$reference : null;
    }

    public static function startsWithWordCharacter(string $text): bool
    {
        return preg_match('/\A' . self::WORD_CHARACTER . '/u', $text) === 1;
    }

    /**
     * `\z` instead of `$`, which also matches before a final line feed.
     */
    public static function endsWithWordCharacter(string $text): bool
    {
        return preg_match('/' . self::WORD_CHARACTER . '\z/u', $text) === 1;
    }

    /**
     * @return string the word characters at the end of `$text`
     */
    public static function trailingWord(string $text): string
    {
        return preg_match('/' . self::WORD_CHARACTER . '+\z/u', $text, $matches) === 1 ? $matches[0] : '';
    }

    /**
     * @return string the word characters at the start of `$text`
     */
    public static function leadingWord(string $text): string
    {
        return preg_match('/\A' . self::WORD_CHARACTER . '+/u', $text, $matches) === 1 ? $matches[0] : '';
    }
}
