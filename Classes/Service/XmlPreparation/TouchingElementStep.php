<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service\XmlPreparation;

/**
 * Sends inline elements that touch each other within a word, like the buttons `<a>Call</a><a>Book</a>` of issue
 * #665, as an element listed in `splitting_tags`: `<dlt-s dlt-r="1" href="…">Call</dlt-s>`.
 *
 * The visible text of such elements is one word (`CallBook`). With the inline elements in `non_splitting_tags`,
 * which issue #489 needs, DeepL translated it as one compound and spread it over the links (`WhatsApp`,
 * `-Telefon`, `buch`), or merged the links (#665). A splitting element "always causes a split", so each text is
 * translated on its own. The split applies to the whole sentence: in `Reach us by <a>Whatsapp</a><a>phone</a>
 * or on the contact page.` DeepL translates `Reach us by`, each link text and `or on the contact page.` as
 * separate segments. That kept the links apart in German and French, a language that moves the verb or the
 * object across the links can get fragments there, like #489 had them. Elements that do not touch keep their
 * sentence together.
 *
 * Only elements touching with a word character on both sides of the boundary are renamed: `Whatsapp </a><a>Call`
 * has a space and is left alone. The revert takes the name and the attributes from the {@see PreparationRecord}
 * by the reference number, like the placeholders get their markup from it. An element without a known number,
 * which DeepL would have had to invent, is replaced by its content rather than turned into an element of a
 * guessed name.
 *
 * @internal
 */
final class TouchingElementStep implements XmlPreparationStepInterface
{
    public function prepare(\DOMElement $content, PreparationRecord $record): void
    {
        $touching = [];
        foreach (DomTree::elements($content) as $element) {
            $next = $element->nextSibling;
            if ($next instanceof \DOMElement
                && InlineMarkup::isInlineElement($element)
                && InlineMarkup::isInlineElement($next)
                && InlineMarkup::endsWithWordCharacter($element->textContent)
                && InlineMarkup::startsWithWordCharacter($next->textContent)
            ) {
                $touching[spl_object_id($element)] = $element;
                $touching[spl_object_id($next)] = $next;
            }
        }
        foreach ($touching as $element) {
            $reference = InlineMarkup::referenceOf($element);
            if ($reference !== null) {
                $record->rememberAttributes($reference, $element);
            }
            DomTree::rename($element, InlineMarkup::SPLITTING_ELEMENT);
        }
    }

    public function revert(\DOMElement $translation, PreparationRecord $record): void
    {
        foreach (DomTree::elements($translation) as $element) {
            if ($element->localName !== InlineMarkup::SPLITTING_ELEMENT) {
                continue;
            }
            $reference = InlineMarkup::referenceOf($element);
            $name = $reference === null ? null : $record->name($reference);
            $attributes = $reference === null ? null : $record->attributes($reference);
            if ($name === null || $attributes === null) {
                DomTree::unwrap($element);
                continue;
            }
            $renamed = DomTree::rename($element, $name);
            foreach (iterator_to_array($renamed->attributes, false) as $attribute) {
                if ($attribute instanceof \DOMAttr && $attribute->nodeName !== InlineMarkup::REFERENCE_ATTRIBUTE) {
                    $renamed->removeAttributeNode($attribute);
                }
            }
            foreach ($attributes as [$namespace, $attributeName, $value]) {
                if ($namespace !== null) {
                    $renamed->setAttributeNS($namespace, $attributeName, $value);
                } else {
                    $renamed->setAttribute($attributeName, $value);
                }
            }
        }
    }
}
