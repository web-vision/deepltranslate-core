<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service\XmlPreparation;

/**
 * Sends a space between a word and an inline element touching it, `the<em>extended</em>warranty` becomes
 * `the <em>extended</em> warranty`, and removes it again where the translation keeps the glued word.
 *
 * Without the space, DeepL reads the word and the text of the element as one word and has no place for the tags
 * (issue #311 / DPL-13): elements were dropped, `e<strong>Mail</strong>` lost its `<strong>` and a link was split
 * into `Versan<a>d</a> un<a>d</a>`. DeepL support suggested a space in #311. Zero-width characters do not work
 * instead, DeepL removed the elements next to them completely in every call.
 *
 * The revert removes the space only if both words at the boundary are still the words of the source, compared
 * without case: the token was not translated, like `FOO<b>BAR</b>`. What the translation writes as two words
 * stays apart: `Produkt<strong>neuheiten</strong>` translated to `product <strong>news</strong>`, and also
 * `Hotel<em>zimmer</em>` translated to `hotel <em>room</em>`, where only the first word is unchanged. A
 * `<sup>` or `<sub>` is no word of its own, there the word outside decides alone, so `1<sup>st</sup>` translated
 * to `1 <sup>er</sup>` comes back as `1<sup>er</sup>`. For the same reason a translated authoring error like
 * `Our<strong>new</strong>offer`, which renders as one word, comes back with spaces as
 * `Unser <strong>neues</strong> Angebot`. That is intended: the translation is new text, and readable spacing is
 * the better result than gluing translated words to an element. A compound whose
 * parts the translation keeps, like `Online<strong>shop</strong>` to `online <strong>shop</strong>`, is glued
 * again, which is what the source had. The words of the source are kept in the {@see PreparationRecord}, nothing
 * is sent for them.
 *
 * Known limitation: a single styled letter of a word, like `i<em>Phone</em>` or `e<strong>Mail</strong>`, has no
 * word of its own. DeepL translates the token and moves the letter into or out of the element
 * (`<em>„iPhone“</em>`, `„E<strong>-Mail“</strong>`), or drops the element. No text is lost and the sentence is
 * translated. Keeping the token with `translate="no"` would keep the markup, but leaves the word untranslated and
 * changes the meaning of the sentence (`please email us at e<strong>Mail</strong>`).
 *
 * Inline elements touching each other are left to {@see TouchingElementStep}.
 *
 * @internal
 */
final class GluedWordStep implements XmlPreparationStepInterface
{
    private const BEFORE = 'before';
    private const AFTER = 'after';

    public function prepare(\DOMElement $content, PreparationRecord $record): void
    {
        $document = DomTree::document($content);
        $glued = [];
        foreach (DomTree::elements($content) as $parent) {
            foreach (iterator_to_array($parent->childNodes) as $node) {
                $next = $node->nextSibling;
                if ($node instanceof \DOMText && $next instanceof \DOMElement && InlineMarkup::isInlineElement($next)) {
                    $element = $next;
                    $side = self::BEFORE;
                    $outside = InlineMarkup::trailingWord($node->data);
                    $inside = InlineMarkup::startsWithWordCharacter($element->textContent);
                } elseif ($next instanceof \DOMText && $node instanceof \DOMElement && InlineMarkup::isInlineElement($node)) {
                    $element = $node;
                    $side = self::AFTER;
                    $outside = InlineMarkup::leadingWord($next->data);
                    $inside = InlineMarkup::endsWithWordCharacter($element->textContent);
                } else {
                    continue;
                }
                $reference = InlineMarkup::referenceOf($element);
                if ($outside !== '' && $inside && $reference !== null) {
                    $glued[] = [$element, $reference, $side, $outside];
                    $parent->insertBefore($document->createTextNode(' '), $next);
                }
            }
        }
        // The words inside are taken once every space is in place, nested glue changes them: Our<b>new<i>big</i></b>.
        foreach ($glued as [$element, $reference, $side, $outside]) {
            $inside = $side === self::BEFORE
                ? InlineMarkup::leadingWord($element->textContent)
                : InlineMarkup::trailingWord($element->textContent);
            $record->rememberGlue($reference, $side, $outside, $inside);
        }
    }

    public function revert(\DOMElement $translation, PreparationRecord $record): void
    {
        foreach (DomTree::elements($translation) as $element) {
            $reference = InlineMarkup::referenceOf($element);
            if ($reference === null) {
                continue;
            }
            $glue = $record->glue($reference);
            // An index or exponent is not a word of its own, the number before it decides: 1<sup>st</sup>.
            $insideMustMatch = !in_array($record->name($reference), ['sup', 'sub'], true);
            $previous = $element->previousSibling;
            if (isset($glue[self::BEFORE])
                && $previous instanceof \DOMText
                && preg_match('/(?<word>' . InlineMarkup::WORD_CHARACTER . '+)(?<spaces> +)\z/u', $previous->data, $matches) === 1
                && $this->isSameWord($matches['word'], $glue[self::BEFORE]['outside'])
                && $this->isGluable(InlineMarkup::leadingWord($element->textContent), $glue[self::BEFORE]['inside'], $insideMustMatch)
            ) {
                $previous->data = substr($previous->data, 0, -strlen($matches['spaces']));
            }
            $next = $element->nextSibling;
            if (isset($glue[self::AFTER])
                && $next instanceof \DOMText
                && preg_match('/\A(?<spaces> +)(?<word>' . InlineMarkup::WORD_CHARACTER . '+)/u', $next->data, $matches) === 1
                && $this->isSameWord($matches['word'], $glue[self::AFTER]['outside'])
                && $this->isGluable(InlineMarkup::trailingWord($element->textContent), $glue[self::AFTER]['inside'], $insideMustMatch)
            ) {
                $next->data = substr($next->data, strlen($matches['spaces']));
            }
        }
    }

    private function isGluable(string $translated, string $source, bool $mustMatch): bool
    {
        return $translated !== '' && (!$mustMatch || $this->isSameWord($translated, $source));
    }

    private function isSameWord(string $translated, string $source): bool
    {
        return mb_strtolower($translated, 'UTF-8') === mb_strtolower($source, 'UTF-8');
    }
}
