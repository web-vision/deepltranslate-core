<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use Masterminds\HTML5\Elements;
use Masterminds\HTML5\Serializer\OutputRules;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\InlineMarkup;

/**
 * Writes HTML the way TYPO3 stores rich text:
 *
 * - void elements as `<br />`, `<hr />` and `<img … />`. The persistence transformation of the rich text editor drops
 *   an `<hr>` on its own line, but keeps `<hr />`.
 * - `<` and `>` in attribute values escaped. The tag scanner of `parseFunc` in the frontend ends a tag at the first
 *   `>`, also inside quotes, so `title="Home > Products"` would cut the link apart.
 * - the attributes of an element carrying a number in `dlt-a` as the attribute text of the source with that number,
 *   passed in the option {@see self::SOURCE_ATTRIBUTES_OPTION}, see
 *   {@see \WebVision\Deepltranslate\Core\Service\XmlPreparation\SourceTags}.
 *
 * @internal
 */
#[Exclude]
final class RichTextOutputRules extends OutputRules
{
    public const SOURCE_ATTRIBUTES_OPTION = 'deepltranslate_source_attributes';

    /**
     * @var array<int, string>
     */
    private array $sourceAttributes;

    /**
     * @param resource $output
     * @param array<string, mixed> $options
     */
    public function __construct($output, $options = [])
    {
        parent::__construct($output, $options);
        $sourceAttributes = $options[self::SOURCE_ATTRIBUTES_OPTION] ?? [];
        $this->sourceAttributes = is_array($sourceAttributes) ? $sourceAttributes : [];
    }

    /**
     * @param \DOMElement $ele
     * @return $this
     */
    protected function attrs($ele)
    {
        $number = $ele->getAttribute(InlineMarkup::SOURCE_ATTRIBUTES_ATTRIBUTE);
        if (preg_match('/^[0-9]+$/', $number) === 1 && isset($this->sourceAttributes[(int)$number])) {
            $this->wr($this->sourceAttributes[(int)$number]);
            return $this;
        }
        return parent::attrs($ele);
    }

    /**
     * @param \DOMElement $ele
     */
    protected function openTag($ele): void
    {
        if ($this->outputMode !== static::IM_IN_HTML || !Elements::isA((string)$ele->localName, Elements::VOID_TAG)) {
            parent::openTag($ele);
            return;
        }
        $this->wr('<')->wr((string)($this->traverser->isLocalElement($ele) ? $ele->localName : $ele->tagName));
        $this->attrs($ele);
        $this->namespaceAttrs($ele);
        $this->wr(' />');
    }

    /**
     * @param string $text
     * @param bool $attribute
     */
    protected function escape($text, $attribute = false): string
    {
        $escaped = parent::escape($text, $attribute);
        return $attribute ? strtr($escaped, ['<' => '&lt;', '>' => '&gt;']) : $escaped;
    }
}
