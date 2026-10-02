<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service\XmlPreparation;

/**
 * What the preparation of one content decided, for the revert of its translation.
 *
 * Nothing of it is sent to DeepL, the XML carries only the reference numbers. The converter fills a new record
 * for every call: on the way to DeepL while preparing the content, on the way back by preparing the source
 * content once more, which gives the same record because the preparation is deterministic. The steps stay
 * stateless, the record lives only as long as one conversion.
 *
 * @internal
 */
final class PreparationRecord
{
    /**
     * @var array<int, string>
     */
    private array $names = [];

    /**
     * @var array<int, \DOMElement>
     */
    private array $elements = [];

    /**
     * @var array<int, list<array{0: ?string, 1: string, 2: string}>>
     */
    private array $attributes = [];

    /**
     * @var array<int, array<string, array{outside: string, inside: string}>>
     */
    private array $glue = [];

    private bool $scriptDigits = false;

    /**
     * @var array<int, string>
     */
    private array $sourceAttributes = [];

    public function rememberName(int $reference, string $name): void
    {
        $this->names[$reference] = $name;
    }

    /**
     * @return string|null the name the element with this reference number has in the source
     */
    public function name(int $reference): ?string
    {
        return $this->names[$reference] ?? null;
    }

    /**
     * Keeps a copy of the element as it is in the source, without reference numbers.
     */
    public function rememberElement(int $reference, \DOMElement $element): void
    {
        $copy = $element->cloneNode(true);
        if ($copy instanceof \DOMElement) {
            foreach (DomTree::elements($copy) as $descendant) {
                $descendant->removeAttribute(InlineMarkup::REFERENCE_ATTRIBUTE);
            }
            $this->elements[$reference] = $copy;
        }
    }

    /**
     * @return \DOMElement|null a copy of the element of the source, import it before inserting it
     */
    public function element(int $reference): ?\DOMElement
    {
        return $this->elements[$reference] ?? null;
    }

    /**
     * Keeps the attributes the element has in the source, its reference number aside.
     */
    public function rememberAttributes(int $reference, \DOMElement $element): void
    {
        $this->attributes[$reference] = [];
        foreach ($element->attributes as $attribute) {
            if ($attribute instanceof \DOMAttr && $attribute->nodeName !== InlineMarkup::REFERENCE_ATTRIBUTE) {
                $this->attributes[$reference][] = [$attribute->namespaceURI, $attribute->nodeName, $attribute->value];
            }
        }
    }

    /**
     * @return list<array{0: ?string, 1: string, 2: string}>|null namespace, name and value of each attribute
     */
    public function attributes(int $reference): ?array
    {
        return $this->attributes[$reference] ?? null;
    }

    /**
     * @param string $side `before` or `after`
     * @param string $outside the word of the source glued to the element on that side
     * @param string $inside the word of the element on that side
     */
    public function rememberGlue(int $reference, string $side, string $outside, string $inside): void
    {
        $this->glue[$reference][$side] = ['outside' => $outside, 'inside' => $inside];
    }

    /**
     * @return array<string, array{outside: string, inside: string}> per glued side, the words of the source
     */
    public function glue(int $reference): array
    {
        return $this->glue[$reference] ?? [];
    }

    /**
     * Keeps the attribute text of a start tag of the source, see {@see SourceTags}.
     */
    public function rememberSourceAttributes(int $number, string $attributes): void
    {
        $this->sourceAttributes[$number] = $attributes;
    }

    /**
     * @return array<int, string> the attribute text of the source by the number in `dlt-a`
     */
    public function sourceAttributes(): array
    {
        return $this->sourceAttributes;
    }

    public function useScriptDigits(): void
    {
        $this->scriptDigits = true;
    }

    public function usesScriptDigits(): bool
    {
        return $this->scriptDigits;
    }
}
