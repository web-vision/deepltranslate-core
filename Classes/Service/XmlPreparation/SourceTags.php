<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service\XmlPreparation;

use Masterminds\HTML5\Elements;

/**
 * Reads the HTML source before masterminds/html5 parses it, the way its tokenizer reads it, for two things:
 *
 * - A `<` that starts no tag is escaped, masterminds/html5 drops it otherwise ("Kinder < 12 Jahre"). The content of
 *   raw text elements like `<script>` and `<style>` is kept as it is, HTML decodes no entity there.
 * - With `$mark`, every start tag gets the attribute `dlt-a` with a number, and the attribute text of the tag
 *   is returned under that number. The parser drops attribute names XML does not allow, like `@click`, `#ref` or
 *   `[disabled]`, lowercases names and keeps only the first of duplicate attributes, the serializer escapes
 *   values. The attribute text of the source restores them byte by byte, see
 *   {@see \WebVision\Deepltranslate\Core\Service\RichTextOutputRules}.
 *
 * Tags the tokenizer reads as broken (end of content inside the tag, `<` inside the attribute list) are not
 * marked, their attributes are written as parsed.
 *
 * @internal
 */
final class SourceTags
{
    private const WHITESPACE = "\n\t\f\r ";

    private const TAG_NAME_CHARACTERS = ':_-0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    private function __construct() {}

    /**
     * @return array{0: string, 1: array<int, string>} the HTML to parse, and the attribute text of each marked start
     *         tag by its number, without the space before `>` and without the `/` of a self-closing tag
     */
    public static function prepare(string $html, bool $mark): array
    {
        $prepared = '';
        $attributes = [];
        $length = strlen($html);
        $position = 0;
        while ($position < $length) {
            $lessThan = strpos($html, '<', $position);
            if ($lessThan === false) {
                $prepared .= substr($html, $position);
                break;
            }
            $prepared .= substr($html, $position, $lessThan - $position);
            $next = $html[$lessThan + 1] ?? '';
            if ($next === '!') {
                $end = self::markupDeclarationEnd($html, $lessThan);
            } elseif ($next === '/') {
                $end = self::untilGreaterThan($html, $lessThan);
            } elseif ($next === '?') {
                $end = preg_match('/\G<\?[a-zA-Z]+[\n\t\f\r ]/', $html, $match, 0, $lessThan) === 1
                    ? self::after($html, '?>', $lessThan)
                    : self::untilGreaterThan($html, $lessThan);
            } elseif (ctype_alpha($next)) {
                $tag = self::startTag($html, $lessThan);
                if ($mark && $tag['complete']) {
                    $number = count($attributes);
                    $attributes[$number] = rtrim(
                        substr($html, $tag['attributesStart'], $tag['attributesEnd'] - $tag['attributesStart']),
                        self::WHITESPACE
                    );
                    // After a last attribute without value, like `class=`, the parser would read the number as its
                    // value, it goes before the attributes then.
                    $markAt = $tag['valueMissing'] ? $tag['attributesStart'] : $tag['attributesEnd'];
                    $prepared .= substr($html, $lessThan, $markAt - $lessThan)
                        . sprintf(' %s="%d"', InlineMarkup::SOURCE_ATTRIBUTES_ATTRIBUTE, $number)
                        . substr($html, $markAt, $tag['end'] - $markAt);
                } else {
                    $prepared .= substr($html, $lessThan, $tag['end'] - $lessThan);
                }
                $position = $tag['complete'] ? self::textEnd($html, $tag['end'], $tag['name']) : $tag['end'];
                $prepared .= substr($html, $tag['end'], $position - $tag['end']);
                continue;
            } else {
                $prepared .= '&lt;';
                $position = $lessThan + 1;
                continue;
            }
            $prepared .= substr($html, $lessThan, $end - $lessThan);
            $position = $end;
        }
        return [$prepared, $attributes];
    }

    /**
     * Reads a start tag like `Tokenizer::tagName()` of masterminds/html5.
     *
     * @return array{name: string, attributesStart: int, attributesEnd: int, end: int, complete: bool, valueMissing: bool}
     *         `end` is the position after the tag, `attributesEnd` the position of the `/` of a self-closing tag or of
     *         the `>`, `valueMissing` tells whether the last attribute ends with `=` and no value
     */
    private static function startTag(string $html, int $lessThan): array
    {
        $position = $lessThan + 1;
        $nameLength = strspn($html, self::TAG_NAME_CHARACTERS, $position);
        $name = strtolower(substr($html, $position, $nameLength));
        $position += $nameLength;
        $attributesStart = $position;
        $valueMissing = false;
        while (true) {
            $position += strspn($html, self::WHITESPACE, $position);
            $character = $html[$position] ?? '';
            if ($character === '<') {
                return ['name' => $name, 'attributesStart' => $attributesStart, 'attributesEnd' => $position, 'end' => $position, 'complete' => false, 'valueMissing' => $valueMissing];
            }
            if ($character !== '/' && $character !== '>' && $character !== '') {
                $position = self::attribute($html, $position, $valueMissing);
            }
            $character = $html[$position] ?? '';
            if ($character === '') {
                return ['name' => $name, 'attributesStart' => $attributesStart, 'attributesEnd' => $position, 'end' => $position, 'complete' => false, 'valueMissing' => $valueMissing];
            }
            if ($character === '>') {
                return ['name' => $name, 'attributesStart' => $attributesStart, 'attributesEnd' => $position, 'end' => $position + 1, 'complete' => true, 'valueMissing' => $valueMissing];
            }
            if ($character === '/') {
                $slash = $position;
                $position++;
                $position += strspn($html, self::WHITESPACE, $position);
                $character = $html[$position] ?? '';
                if ($character === '>') {
                    return ['name' => $name, 'attributesStart' => $attributesStart, 'attributesEnd' => $slash, 'end' => $position + 1, 'complete' => true, 'valueMissing' => false];
                }
                if ($character === '') {
                    return ['name' => $name, 'attributesStart' => $attributesStart, 'attributesEnd' => $position, 'end' => $position, 'complete' => false, 'valueMissing' => false];
                }
            }
        }
    }

    /**
     * Reads one attribute like `Tokenizer::attribute()` of masterminds/html5.
     *
     * @param bool $valueMissing set to whether the attribute has a `=` without a value, like `class=` before `>`
     * @return int the position after the attribute
     */
    private static function attribute(string $html, int $position, bool &$valueMissing): int
    {
        $valueMissing = false;
        $nameLength = strcspn($html, '/>=' . self::WHITESPACE, $position);
        $position += $nameLength === 0 ? 1 : $nameLength;
        $position += strspn($html, self::WHITESPACE, $position);
        if (($html[$position] ?? '') !== '=') {
            return $position;
        }
        $position++;
        $position += strspn($html, self::WHITESPACE, $position);
        $character = $html[$position] ?? '';
        if ($character === '"' || $character === "'") {
            $position++;
            $position += strcspn($html, "\f" . $character, $position);
            return min($position + 1, strlen($html));
        }
        if ($character === '>' || $character === '') {
            $valueMissing = true;
            return $position;
        }
        return $position + strcspn($html, '>' . self::WHITESPACE, $position);
    }

    /**
     * The content of raw text elements ends at their exact end tag, of escapable raw text elements (`<textarea>`,
     * `<title>`) at `</` and their name, as masterminds/html5 reads them.
     *
     * @return int the position after the text of the element, or the given position for other elements
     */
    private static function textEnd(string $html, int $position, string $name): int
    {
        $mode = Elements::element($name) & (Elements::TEXT_RAW | Elements::TEXT_RCDATA);
        if ($mode === 0) {
            return $position;
        }
        $end = stripos($html, $mode === Elements::TEXT_RAW ? '</' . $name . '>' : '</' . $name, $position);
        return $end === false ? strlen($html) : $end;
    }

    private static function markupDeclarationEnd(string $html, int $lessThan): int
    {
        if (substr($html, $lessThan, 4) === '<!--') {
            if (($html[$lessThan + 4] ?? '') === '>') {
                return $lessThan + 5;
            }
            $ends = array_filter([strpos($html, '-->', $lessThan + 4), strpos($html, '--!>', $lessThan + 4)], 'is_int');
            if ($ends === []) {
                return strlen($html);
            }
            $end = min($ends);
            return $end + (substr($html, $end, 4) === '--!>' ? 4 : 3);
        }
        if (substr($html, $lessThan, 9) === '<![CDATA[') {
            return self::after($html, ']]>', $lessThan + 9);
        }
        return self::untilGreaterThan($html, $lessThan);
    }

    private static function untilGreaterThan(string $html, int $position): int
    {
        return self::after($html, '>', $position);
    }

    private static function after(string $html, string $needle, int $position): int
    {
        $found = strpos($html, $needle, $position);
        return $found === false ? strlen($html) : $found + strlen($needle);
    }
}
