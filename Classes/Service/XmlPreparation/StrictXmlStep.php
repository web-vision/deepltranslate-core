<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service\XmlPreparation;

/**
 * Removes what HTML allows and XML 1.0 does not. DeepL parses XML strictly since tag handling v2 and rejects the
 * whole field with "Tag handling parsing failed", the field then stays untranslated (issue #507, there for
 * `&nbsp;`).
 *
 * - Characters outside the `Char` production of XML 1.0 (https://www.w3.org/TR/xml/#charsets), in text,
 *   attribute values and comments. U+000B, the manual line break of word processors, and U+000C become a space,
 *   so the words around them stay apart. Other control characters, U+FFFE and U+FFFF are removed.
 * - Processing instructions, the HTML parser keeps `<?xml …?>` as one, which XML allows only at the start of a
 *   document. Rich text has nothing to translate in them.
 * - `--` inside a comment and a comment ending in `-`, which XML forbids
 *   (https://www.w3.org/TR/xml/#sec-comments). A space is inserted, the comment stays.
 *
 * Nothing is reverted: the removed characters have no meaning in rich text. The step runs last, so every other
 * step decides on the content as it was stored.
 *
 * @internal
 */
final class StrictXmlStep implements XmlPreparationStepInterface
{
    private const NOT_XML_CHARACTER = '/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u';

    public function prepare(\DOMElement $content, PreparationRecord $record): void
    {
        foreach (DomTree::query($content, './/processing-instruction()') as $instruction) {
            $instruction->parentNode?->removeChild($instruction);
        }
        foreach (DomTree::query($content, './/text()') as $text) {
            if ($text instanceof \DOMText) {
                $text->data = $this->replaceCharacters($text->data);
            }
        }
        foreach (DomTree::query($content, './/comment()') as $comment) {
            if ($comment instanceof \DOMComment) {
                $comment->data = $this->separateHyphens($this->replaceCharacters($comment->data));
            }
        }
        foreach (DomTree::elements($content) as $element) {
            foreach (iterator_to_array($element->attributes ?? [], false) as $attribute) {
                if (!$attribute instanceof \DOMAttr) {
                    continue;
                }
                $value = $this->replaceCharacters($attribute->value);
                if ($value !== $attribute->value) {
                    DomTree::setAttribute($element, $attribute, $value);
                }
            }
        }
    }

    public function revert(\DOMElement $translation, PreparationRecord $record): void {}

    private function replaceCharacters(string $text): string
    {
        $text = str_replace(["\u{B}", "\u{C}"], ' ', $text);
        return preg_replace(self::NOT_XML_CHARACTER, '', $text) ?? $text;
    }

    private function separateHyphens(string $comment): string
    {
        $comment = preg_replace('/-(?=-)/', '- ', $comment) ?? $comment;
        return str_ends_with($comment, '-') ? $comment . ' ' : $comment;
    }
}
