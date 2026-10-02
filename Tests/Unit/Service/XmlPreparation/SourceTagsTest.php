<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Service\XmlPreparation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\SourceTags;

#[CoversClass(SourceTags::class)]
final class SourceTagsTest extends UnitTestCase
{
    public static function prepareDataProvider(): \Generator
    {
        yield 'attribute text of every start tag, without the space before > and the / of a self-closing tag' => [
            'html' => '<div class="a" @click="x()" ><br/><img src="x.jpg" alt="" /></div>',
            'expectedHtml' => '<div class="a" @click="x()"  dlt-a="0"><br dlt-a="1"/><img src="x.jpg" alt=""  dlt-a="2"/></div>',
            'expectedAttributes' => [' class="a" @click="x()"', '', ' src="x.jpg" alt=""'],
        ];
        yield 'quoted values with > and unquoted values' => [
            'html' => '<a title="a > b" data-x=1 data-y=\'c\'>x</a>',
            'expectedHtml' => '<a title="a > b" data-x=1 data-y=\'c\' dlt-a="0">x</a>',
            'expectedAttributes' => [' title="a > b" data-x=1 data-y=\'c\''],
        ];
        yield 'a less-than sign starting no tag is escaped, comments and end tags are kept' => [
            'html' => '<p>a < b <3</p><!-- <p class="x"> --><?php echo 1; ?>',
            'expectedHtml' => '<p dlt-a="0">a &lt; b &lt;3</p><!-- <p class="x"> --><?php echo 1; ?>',
            'expectedAttributes' => [''],
        ];
        yield 'raw text of script and style is kept, also without end tag' => [
            'html' => '<style>p > a {}</style><script>if (a < b) { x = "<p class=\'y\'>"; }',
            'expectedHtml' => '<style dlt-a="0">p > a {}</style><script dlt-a="1">if (a < b) { x = "<p class=\'y\'>"; }',
            'expectedAttributes' => ['', ''],
        ];
        yield 'escapable raw text of textarea' => [
            'html' => '<textarea><b class="x"></textarea>',
            'expectedHtml' => '<textarea dlt-a="0"><b class="x"></textarea>',
            'expectedAttributes' => [''],
        ];
        yield 'last attribute without value, the number goes before the attributes' => [
            'html' => '<p class=>a</p><p id="x" class= >b</p><img alt= /><p class=a=>c</p>',
            'expectedHtml' => '<p dlt-a="0" class=>a</p><p dlt-a="1" id="x" class= >b</p><img alt= / dlt-a="2"><p class=a= dlt-a="3">c</p>',
            'expectedAttributes' => [' class=', ' id="x" class=', ' alt= /', ' class=a='],
        ];
        yield 'tag broken by a less-than sign or the end of the content is not marked' => [
            'html' => '<p class="a" <b>x</b><i title="t"',
            'expectedHtml' => '<p class="a" <b dlt-a="0">x</b><i title="t"',
            'expectedAttributes' => [''],
        ];
    }

    /**
     * @param list<string> $expectedAttributes
     */
    #[Test]
    #[DataProvider('prepareDataProvider')]
    public function prepareMarksStartTagsAndReturnsTheirAttributeText(string $html, string $expectedHtml, array $expectedAttributes): void
    {
        static::assertSame([$expectedHtml, $expectedAttributes], SourceTags::prepare($html, true));
    }

    #[Test]
    public function prepareWithoutMarkingOnlyEscapes(): void
    {
        static::assertSame(
            ['<p class="a">a &lt; b</p><script>a < b</script>', []],
            SourceTags::prepare('<p class="a">a < b</p><script>a < b</script>', false)
        );
    }
}
