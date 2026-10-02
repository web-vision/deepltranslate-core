<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service\XmlPreparation;

/**
 * Sends a `<sup>` or `<sub>` holding only digits as Unicode superscript or subscript digits: `m<sup>2</sup>`
 * becomes `m²`, `H<sub>2</sub>O` becomes `H₂O`.
 *
 * The digit is part of the word before it (`m2`, `20211`), so DeepL has no word to place the tag on. It pulled
 * the word into the element (`<sup>m²</sup>`), dropped the element (`2021<sup>1</sup>` became `20211`,
 * `10<sup>3</sup> kg` became `103 kg`) or made `Hallo12` of `Hello1<sup>2</sup>` (DPL-89). DeepL keeps script
 * digits as they are and even writes them itself, so `m²` comes back as `m²`. The revert turns every script
 * digit back into an element.
 *
 * The step applies only if the content has no script digit of its own, otherwise the revert could not tell
 * them apart. An element with attributes is left to {@see ScriptPlaceholderStep}, the attributes would be lost.
 * Adjacent elements come back as one (`<sup>1</sup><sup>2</sup>` as `<sup>12</sup>`), which renders the same.
 *
 * @internal
 */
final class ScriptDigitStep implements XmlPreparationStepInterface
{
    private const SUPERSCRIPT_DIGITS = ['⁰', '¹', '²', '³', '⁴', '⁵', '⁶', '⁷', '⁸', '⁹'];
    private const SUBSCRIPT_DIGITS = ['₀', '₁', '₂', '₃', '₄', '₅', '₆', '₇', '₈', '₉'];
    private const SUPERSCRIPT_RUN = '[⁰¹²³⁴⁵⁶⁷⁸⁹]+';
    private const SUBSCRIPT_RUN = '[₀₁₂₃₄₅₆₇₈₉]+';
    private const SCRIPT_DIGIT = '/[⁰¹²³⁴⁵⁶⁷⁸⁹₀₁₂₃₄₅₆₇₈₉]/u';

    public function prepare(\DOMElement $content, PreparationRecord $record): void
    {
        $elements = $this->findDigitElements($content);
        if ($elements === [] || preg_match(self::SCRIPT_DIGIT, $content->textContent) === 1) {
            return;
        }
        $record->useScriptDigits();
        foreach ($elements as $element) {
            $digits = $element->localName === 'sub' ? self::SUBSCRIPT_DIGITS : self::SUPERSCRIPT_DIGITS;
            $element->parentNode?->replaceChild(
                DomTree::document($content)->createTextNode(strtr($element->textContent, $digits)),
                $element
            );
        }
        $content->normalize();
    }

    public function revert(\DOMElement $translation, PreparationRecord $record): void
    {
        if (!$record->usesScriptDigits()) {
            return;
        }
        $document = DomTree::document($translation);
        foreach (DomTree::query($translation, './/text()') as $text) {
            if (!$text instanceof \DOMText || preg_match(self::SCRIPT_DIGIT, $text->data) !== 1) {
                continue;
            }
            $fragment = $document->createDocumentFragment();
            $parts = preg_split(
                '/(' . self::SUPERSCRIPT_RUN . '|' . self::SUBSCRIPT_RUN . ')/u',
                $text->data,
                -1,
                PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
            ) ?: [];
            foreach ($parts as $part) {
                if (preg_match('/\A' . self::SUPERSCRIPT_RUN . '\z/u', $part) === 1) {
                    $fragment->appendChild($document->createElement('sup', strtr($part, array_flip(self::SUPERSCRIPT_DIGITS))));
                } elseif (preg_match('/\A' . self::SUBSCRIPT_RUN . '\z/u', $part) === 1) {
                    $fragment->appendChild($document->createElement('sub', strtr($part, array_flip(self::SUBSCRIPT_DIGITS))));
                } else {
                    $fragment->appendChild($document->createTextNode($part));
                }
            }
            $text->parentNode?->replaceChild($fragment, $text);
        }
    }

    /**
     * @return list<\DOMElement> `<sup>` and `<sub>` without attributes of their own, the reference number aside,
     *                           holding one to four digits as their only child
     */
    private function findDigitElements(\DOMElement $content): array
    {
        return array_values(array_filter(
            DomTree::elements($content),
            static fn(\DOMElement $element): bool => in_array($element->localName, ['sup', 'sub'], true)
                && $element->attributes->length === ($element->hasAttribute(InlineMarkup::REFERENCE_ATTRIBUTE) ? 1 : 0)
                && $element->childNodes->length === 1
                && $element->firstChild instanceof \DOMText
                && preg_match('/\A[0-9]{1,4}\z/', $element->textContent) === 1
        ));
    }
}
