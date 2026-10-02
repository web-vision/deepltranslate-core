<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use Masterminds\HTML5;
use Masterminds\HTML5\Elements;

/**
 * Tells whether content of {@see \WebVision\Deepltranslate\Core\Domain\Enum\ContentFormat::Unknown} format is plain
 * text, so it is escaped before it is sent to DeepL instead of being parsed as HTML.
 *
 * Content counts as plain text when the HTML parser would change it: it reports a parse error ("a<b",
 * "I </3 you", "Ref <title> & co"), or it creates an element whose end tag is missing in the content
 * ("Use <b> for bold", "Press <Enter>"). Rich text written by the rich text editor closes every element, so it is
 * still handled as HTML. Content without anything the parser reads as a tag is handled as HTML as before, the
 * result is the same for both formats apart from entities written as text.
 *
 * A `&` does not count: masterminds/html5 reports every `&` it cannot decode as an error ("Q&A", "&copy 2026",
 * "?id=1&lang=de" in a link), but keeps it in the text as written, like a browser does with "Q&A".
 *
 * @internal
 */
final readonly class PlainTextDetector
{
    public function isPlainText(string $content): bool
    {
        if (preg_match('#<[a-zA-Z!/?]#', $content) !== 1) {
            return false;
        }
        $html5 = new HTML5(['disable_html_ns' => true]);
        // The same escaping as in HtmlXmlConverter, a "<" not starting a tag is text. Every "&" is escaped, so only
        // errors of the markup are left.
        $fragment = $html5->loadHTMLFragment(
            (string)preg_replace('#<(?![a-zA-Z!/?])#', '&lt;', str_replace('&', '&amp;', $content))
        );
        if ($html5->hasErrors()) {
            return true;
        }
        $elementCount = [];
        $this->countElements($fragment, $elementCount);
        foreach ($elementCount as $name => $count) {
            if (Elements::isA($name, Elements::VOID_TAG)) {
                continue;
            }
            $quotedName = preg_quote($name, '#');
            $closedCount = (int)preg_match_all('#</' . $quotedName . '[\s/>]|<' . $quotedName . '(?:\s[^<>]*)?/>#i', $content);
            if ($closedCount < $count) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<string, int> $elementCount
     */
    private function countElements(\DOMNode $node, array &$elementCount): void
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $name = strtolower($child->nodeName);
                $elementCount[$name] = ($elementCount[$name] ?? 0) + 1;
                $this->countElements($child, $elementCount);
            }
        }
    }
}
