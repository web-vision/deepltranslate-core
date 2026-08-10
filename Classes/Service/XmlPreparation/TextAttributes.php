<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service\XmlPreparation;

/**
 * The attributes whose values readers see or hear as text: the tooltip in `title`, the alternative text of an
 * image in `alt` and the name screen readers read out in `aria-label` (issue #427, the title of a link).
 *
 * DeepL translates the content of elements and never the values of attributes. The values are therefore sent
 * as texts of their own in the same request, see {@see \WebVision\Deepltranslate\Core\Translator::translate()},
 * and put back by value: DeepL keeps the attributes of every element, so an element of the translation still
 * carries the value of its source element, also when DeepL moved or copied the element. Equal values get the
 * same translation and are sent once.
 *
 * A value is not translated
 *
 * - inside content marked as not to be translated, by `translate="no"` or the class `notranslate` on the
 *   element or an ancestor. The nearest valid `translate` attribute decides, an invalid value is inherited like
 *   in HTML,
 * - on `<script>` and `<style>`, whose content is not translated either,
 * - without a letter, like a number or a symbol.
 *
 * A value DeepL returns unchanged, like a name, is kept as stored.
 *
 * @internal
 */
final class TextAttributes
{
    public const NAMES = ['title', 'alt', 'aria-label'];

    private const UNTRANSLATED_ELEMENTS = ['script', 'style'];

    private function __construct() {}

    /**
     * @return array<string, string> the values to translate, each once, in the order of the content: the text to
     *                               send by the key of the value, see {@see self::key()}
     */
    public static function values(\DOMElement $content): array
    {
        $values = [];
        foreach (DomTree::elements($content) as $element) {
            if (!self::isTranslatable($element)) {
                continue;
            }
            foreach (self::NAMES as $name) {
                $value = $element->getAttribute($name);
                $key = self::key($value);
                if ($element->hasAttribute($name) && !isset($values[$key]) && preg_match('/\p{L}/u', $key) === 1) {
                    // A line break stays in the text, a browser shows it in the tooltip.
                    $values[$key] = StrictXmlStep::replaceCharacters($value);
                }
            }
        }
        return $values;
    }

    /**
     * Replaces the values of the translation by their translations. An element whose attributes are written as
     * attribute text of the source gets the translation in that text, see {@see SourceTags}.
     *
     * @param array<string, string> $translations the translation by the key of the value, see {@see self::values()}
     */
    public static function translate(\DOMElement $translation, array $translations, PreparationRecord $record): void
    {
        $sourceAttributes = $record->sourceAttributes();
        foreach (DomTree::elements($translation) as $element) {
            if (!self::isTranslatable($element)) {
                continue;
            }
            $values = [];
            foreach (self::NAMES as $name) {
                $key = self::key($element->getAttribute($name));
                $translated = $translations[$key] ?? '';
                if ($element->hasAttribute($name) && $translated !== '' && self::key($translated) !== $key) {
                    $values[$name] = $translated;
                }
            }
            if ($values === []) {
                continue;
            }
            $number = $element->getAttribute(InlineMarkup::SOURCE_ATTRIBUTES_ATTRIBUTE);
            if (preg_match('/^[0-9]+$/', $number) === 1 && isset($sourceAttributes[(int)$number])) {
                $record->rememberSourceAttributes(
                    (int)$number,
                    SourceTags::replaceAttributeValues($sourceAttributes[(int)$number], $values)
                );
            }
            foreach ($values as $name => $value) {
                $element->setAttribute($name, $value);
            }
        }
    }

    /**
     * The value as the XML for DeepL holds it, see {@see StrictXmlStep}, to find it in the translation. Tabs and
     * line breaks are spaces: the XML writes them as character references, DeepL may answer with the character,
     * which an XML parser reads as a space in an attribute.
     */
    public static function key(string $value): string
    {
        return strtr(StrictXmlStep::replaceCharacters($value), ["\t" => ' ', "\n" => ' ', "\r" => ' ']);
    }

    private static function isTranslatable(\DOMElement $element): bool
    {
        if (in_array($element->localName, self::UNTRANSLATED_ELEMENTS, true)) {
            return false;
        }
        for ($node = $element; $node instanceof \DOMElement; $node = $node->parentNode) {
            $translate = strtolower($node->getAttribute('translate'));
            if ($node->hasAttribute('translate') && in_array($translate, ['yes', 'no', ''], true)) {
                return $translate !== 'no';
            }
            if (in_array('notranslate', preg_split('/\s+/', $node->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY) ?: [], true)) {
                return false;
            }
        }
        return true;
    }
}
