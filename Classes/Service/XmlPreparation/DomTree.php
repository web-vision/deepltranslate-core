<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service\XmlPreparation;

/**
 * Tree operations the preparation steps share.
 *
 * @internal
 */
final class DomTree
{
    private function __construct()
    {
    }

    /**
     * @return list<\DOMElement> the element and its descendant elements in document order, as a snapshot that
     *                           stays valid while the tree is changed
     */
    public static function elements(\DOMElement $root): array
    {
        $elements = [];
        foreach (self::query($root, 'descendant-or-self::*') as $element) {
            if ($element instanceof \DOMElement) {
                $elements[] = $element;
            }
        }
        return $elements;
    }

    /**
     * @return list<\DOMNode> the result of the XPath expression relative to `$root`, as a snapshot
     */
    public static function query(\DOMElement $root, string $expression): array
    {
        $nodes = [];
        foreach ((new \DOMXPath(self::document($root)))->query($expression, $root) ?: [] as $node) {
            if ($node instanceof \DOMNode) {
                $nodes[] = $node;
            }
        }
        return $nodes;
    }

    /**
     * Replaces the element by an element of another name with the same attributes and children.
     */
    public static function rename(\DOMElement $element, string $name): \DOMElement
    {
        $renamed = self::document($element)->createElement($name);
        // iterator_to_array() accepts no array before PHP 8.2.
        foreach (iterator_to_array($element->attributes ?? new \ArrayIterator(), false) as $attribute) {
            if ($attribute instanceof \DOMAttr) {
                self::setAttribute($renamed, $attribute, $attribute->value);
            }
        }
        while ($element->firstChild !== null) {
            $renamed->appendChild($element->firstChild);
        }
        $element->parentNode?->replaceChild($renamed, $element);
        return $renamed;
    }

    /**
     * Sets the value of an attribute like `$attribute` on the element, with its namespace, like the one of
     * `xml:lang`. Writing DOMAttr::$value instead would parse entity references in the value.
     */
    public static function setAttribute(\DOMElement $element, \DOMAttr $attribute, string $value): void
    {
        if ($attribute->namespaceURI !== null) {
            $element->setAttributeNS($attribute->namespaceURI, $attribute->nodeName, $value);
        } else {
            $element->setAttribute($attribute->nodeName, $value);
        }
    }

    /**
     * Replaces the element by its children.
     */
    public static function unwrap(\DOMElement $element): void
    {
        $parent = $element->parentNode;
        if ($parent === null) {
            return;
        }
        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }
        $parent->removeChild($element);
    }

    public static function document(\DOMNode $node): \DOMDocument
    {
        return $node instanceof \DOMDocument
            ? $node
            : ($node->ownerDocument ?? throw new \LogicException('The node belongs to no document.', 1790938380));
    }
}
