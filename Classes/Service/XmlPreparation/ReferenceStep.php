<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service\XmlPreparation;

/**
 * Gives every inline element of the source a reference number, `<a dlt-r="0" href="…">`, before any other step
 * changes the content, and removes the numbers from the translation after every other step is reverted.
 *
 * The number is the only thing the preparation sends besides the content. It survives everything DeepL does to an
 * element, renaming excepted, so the revert finds the source element of every element of the translation,
 * including the copies DeepL makes (see {@see ElementCopyStep}). Names and markup the revert needs come from
 * the {@see PreparationRecord} instead of attributes, so DeepL cannot change them and the request stays small.
 *
 * @internal
 */
final class ReferenceStep implements XmlPreparationStepInterface
{
    public function prepare(\DOMElement $content, PreparationRecord $record): void
    {
        foreach (self::numberedElements($content) as $reference => $element) {
            $record->rememberName($reference, (string)$element->localName);
            $element->setAttribute(InlineMarkup::REFERENCE_ATTRIBUTE, (string)$reference);
        }
    }

    /**
     * @return list<\DOMElement> the elements of the content getting a reference number, the number is the key.
     *                           For content not prepared yet, it leads from a number to the element as stored.
     */
    public static function numberedElements(\DOMElement $content): array
    {
        return array_values(array_filter(
            DomTree::elements($content),
            static fn(\DOMElement $element): bool => InlineMarkup::isInlineElement($element)
        ));
    }

    public function revert(\DOMElement $translation, PreparationRecord $record): void
    {
        foreach (DomTree::elements($translation) as $element) {
            $element->removeAttribute(InlineMarkup::REFERENCE_ATTRIBUTE);
        }
    }
}
