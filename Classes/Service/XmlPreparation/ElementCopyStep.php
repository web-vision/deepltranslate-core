<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service\XmlPreparation;

/**
 * Repairs the copies DeepL makes of an inline element.
 *
 * With `non_splitting_tags`, DeepL translates a sentence as a whole and puts the tags back on the translated
 * words. When a word moves out of an element, "the tags are duplicated (which is expected here)"
 * (https://developers.deepl.com/docs/translate/translating-xml, "Keep sentences together across tags"). Issue
 * #489 translated to English came back as
 * `<i dlt-r="0">Our </i><a dlt-r="1"><i dlt-r="2">student advisory service</i></a><i dlt-r="3"></i>
 * <i dlt-r="0">will be happy to tell you more about possible career paths</i><i dlt-r="3">.</i>`.
 * The reference numbers of {@see ReferenceStep} tell a copy apart from another element of the same name, so the
 * revert
 *
 * - replaces a copy without text and child elements by its whitespace, if another copy has content,
 *   and keeps only the first of several copies without content, a duplicated placeholder for example,
 * - merges two adjacent formatting elements with the same attributes into one, if one of them is a copy,
 *   which renders the same (`<i>…paths</i><i>.</i>` becomes `<i>…paths.</i>`),
 * - merges two copies of the same source element with only whitespace between them, links included
 *   (`<a>Le</a> <a>ticket n° 670</a>` becomes `<a>Le ticket n° 670</a>`).
 *
 * Copies on separate words are kept, the revert does not guess which text belongs to which copy. German made of
 * `<strong>Today:</strong> the heading … are translated, the button labels … stay English.` the sentence
 * `<strong>Derzeit</strong> werden … übersetzt, <strong>während</strong> die Schaltflächenbeschriftungen …`,
 * a copy on a word DeepL added. Keeping only the first copy would be right there and wrong for #489, where the
 * second copy holds most of the italic sentence. This is DeepL behaviour under `non_splitting_tags`.
 *
 * The repair runs first in the revert, so an empty copy cannot hide its element from the other steps. Element
 * names are taken from the {@see PreparationRecord}, splitting elements still have their helper name then.
 *
 * @internal
 */
final class ElementCopyStep implements XmlPreparationStepInterface
{
    /**
     * Formatting elements, two adjacent of which render like one. Two adjacent links of different source
     * elements are two targets and never merged.
     */
    private const MERGEABLE_ELEMENTS = ['b', 'code', 'em', 'i', 'mark', 's', 'small', 'span', 'strong', 'sub', 'sup', 'u'];

    public function prepare(\DOMElement $content, PreparationRecord $record): void
    {
    }

    public function revert(\DOMElement $translation, PreparationRecord $record): void
    {
        $copied = $this->removeEmptyCopies($translation);
        if ($copied !== []) {
            $this->mergeCopiesSeparatedByWhitespace($translation);
            $this->mergeAdjacentCopies($translation, $record, $copied);
            $translation->normalize();
        }
    }

    /**
     * @return array<int, true> the reference numbers that occur more than once
     */
    private function removeEmptyCopies(\DOMElement $translation): array
    {
        $elementsByReference = [];
        foreach (DomTree::elements($translation) as $element) {
            $reference = InlineMarkup::referenceOf($element);
            if ($reference !== null) {
                $elementsByReference[$reference][] = $element;
            }
        }
        $copied = [];
        foreach ($elementsByReference as $reference => $elements) {
            if (count($elements) < 2) {
                continue;
            }
            $copied[$reference] = true;
            $empty = array_values(array_filter($elements, fn (\DOMElement $element): bool => $this->isEmpty($element)));
            if (count($empty) === count($elements)) {
                array_shift($empty);
            }
            foreach ($empty as $element) {
                DomTree::unwrap($element);
            }
        }
        return $copied;
    }

    /**
     * Two copies of the same source element, separated by nothing or whitespace only, become one element with
     * the whitespace inside: French made `<a>Le</a> <a>ticket n° 670</a>` of one link `#670`, which a reader
     * sees as one link with a gap and a screen reader announces twice. Links are merged too, both copies are
     * the same source link. Different source elements are never merged here.
     */
    private function mergeCopiesSeparatedByWhitespace(\DOMElement $translation): void
    {
        foreach (DomTree::elements($translation) as $element) {
            $reference = InlineMarkup::referenceOf($element);
            if ($reference === null || $element->parentNode === null) {
                continue;
            }
            while (true) {
                $between = $element->nextSibling;
                $next = $between instanceof \DOMText && trim($between->data) === '' ? $between->nextSibling : $between;
                if (!$next instanceof \DOMElement
                    || $next->localName !== $element->localName
                    || InlineMarkup::referenceOf($next) !== $reference
                ) {
                    break;
                }
                if ($between !== $next && $between !== null) {
                    $element->appendChild($between);
                }
                while ($next->firstChild !== null) {
                    $element->appendChild($next->firstChild);
                }
                $next->parentNode?->removeChild($next);
            }
        }
    }

    /**
     * @param array<int, true> $copied
     */
    private function mergeAdjacentCopies(\DOMElement $translation, PreparationRecord $record, array $copied): void
    {
        foreach (DomTree::elements($translation) as $element) {
            $reference = InlineMarkup::referenceOf($element);
            if ($reference === null
                || $element->parentNode === null
                || !in_array($record->name($reference), self::MERGEABLE_ELEMENTS, true)
            ) {
                continue;
            }
            while (($next = $element->nextSibling) instanceof \DOMElement
                && ($nextReference = InlineMarkup::referenceOf($next)) !== null
                && $next->localName === $element->localName
                && $record->name($nextReference) === $record->name($reference)
                && $this->attributesWithoutReference($next) === $this->attributesWithoutReference($element)
                && (isset($copied[$reference]) || isset($copied[$nextReference]))
            ) {
                while ($next->firstChild !== null) {
                    $element->appendChild($next->firstChild);
                }
                $next->parentNode?->removeChild($next);
            }
        }
    }

    private function isEmpty(\DOMElement $element): bool
    {
        foreach ($element->childNodes as $child) {
            if (!$child instanceof \DOMText || trim($child->data) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array<string, string>
     */
    private function attributesWithoutReference(\DOMElement $element): array
    {
        $attributes = [];
        foreach ($element->attributes ?? [] as $attribute) {
            if ($attribute instanceof \DOMAttr && $attribute->nodeName !== InlineMarkup::REFERENCE_ATTRIBUTE) {
                $attributes[$attribute->nodeName] = $attribute->value;
            }
        }
        ksort($attributes);
        return $attributes;
    }
}
