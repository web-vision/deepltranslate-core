<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use Masterminds\HTML5;
use Masterminds\HTML5\Serializer\Traverser;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * masterminds/html5 serializing with {@see RichTextOutputRules}, so the HTML is written the way TYPO3 stores rich
 * text. Parsing is unchanged.
 *
 * @internal
 */
#[Exclude]
final class RichTextHtml5 extends HTML5
{
    /**
     * Same as the parent method, apart from the output rules.
     *
     * @param \DOMDocument|\DOMNodeList<\DOMNode>|\DOMNode $dom
     * @param resource|string $file
     * @param array<string, mixed> $options
     */
    public function save($dom, $file, $options = []): void
    {
        if (is_string($file)) {
            $stream = fopen($file, 'wb');
            if ($stream === false) {
                throw new \RuntimeException(sprintf('The file "%s" cannot be opened for writing.', $file), 1790946718);
            }
        } else {
            $stream = $file;
        }
        $options = array_merge($this->getOptions(), $options);
        $rules = new RichTextOutputRules($stream, $options);
        $traverser = new Traverser($dom, $stream, $rules, $options);
        $traverser->walk();
        $rules->unsetTraverser();
        if (is_string($file)) {
            fclose($stream);
        }
    }
}
