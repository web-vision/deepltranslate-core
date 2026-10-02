<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service\XmlPreparation;

/**
 * Sends a `<sup>` or `<sub>` holding one to four digits, symbols or punctuation marks, like `®`, `*` or `1)`, as
 * an empty placeholder element with its reference number: `FOOBAR<sup>®</sup>` becomes `FOOBAR<dlt-p dlt-r="0"/>`.
 *
 * DeepL pulled the word before such an element into it (`<sup>FOOBAR®</sup>`, issue #311 / DPL-13). A
 * placeholder holds no text, so DeepL places it next to the translation of the word before or after it
 * (https://developers.deepl.com/docs/translate/translating-xml), which is what DeepL support suggested in #311.
 * The revert puts a copy of the source element in place of each placeholder, taken from the
 * {@see PreparationRecord}, nothing DeepL returns is turned into markup.
 *
 * Digits are handled by {@see ScriptDigitStep} first, they reach this step only if the content has script digits
 * of its own or the element has attributes. Text that needs translation, like `<sup>st</sup>`, stays an element.
 * A placeholder DeepL duplicates is an empty copy, {@see ElementCopyStep} keeps only the first.
 *
 * @internal
 */
final class ScriptPlaceholderStep implements XmlPreparationStepInterface
{
    public function prepare(\DOMElement $content, PreparationRecord $record): void
    {
        $document = DomTree::document($content);
        foreach (DomTree::elements($content) as $element) {
            $reference = InlineMarkup::referenceOf($element);
            if ($reference === null
                || !in_array($element->localName, ['sup', 'sub'], true)
                || $element->childNodes->length !== 1
                || !$element->firstChild instanceof \DOMText
                || preg_match('/\A[\p{N}\p{S}\p{P}]{1,4}\z/u', $element->textContent) !== 1
            ) {
                continue;
            }
            $record->rememberElement($reference, $element);
            $placeholder = $document->createElement(InlineMarkup::PLACEHOLDER_ELEMENT);
            $placeholder->setAttribute(InlineMarkup::REFERENCE_ATTRIBUTE, (string)$reference);
            $element->parentNode?->replaceChild($placeholder, $element);
        }
    }

    /**
     * A placeholder without a known reference number is removed, the element is lost then, as it was before this
     * step existed. Text DeepL put into a placeholder is kept after it.
     */
    public function revert(\DOMElement $translation, PreparationRecord $record): void
    {
        $document = DomTree::document($translation);
        foreach (DomTree::elements($translation) as $placeholder) {
            if ($placeholder->localName !== InlineMarkup::PLACEHOLDER_ELEMENT) {
                continue;
            }
            $reference = InlineMarkup::referenceOf($placeholder);
            $original = $reference === null ? null : $record->element($reference);
            $replacement = $document->createDocumentFragment();
            if ($original !== null) {
                $replacement->appendChild($document->importNode($original, true));
            }
            while ($placeholder->firstChild !== null) {
                $replacement->appendChild($placeholder->firstChild);
            }
            if ($replacement->hasChildNodes()) {
                $placeholder->parentNode?->replaceChild($replacement, $placeholder);
            } else {
                $placeholder->parentNode?->removeChild($placeholder);
            }
        }
    }
}
