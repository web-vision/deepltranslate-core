<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WebVision\Deepltranslate\Core\Service\PlainTextDetector;

#[CoversClass(PlainTextDetector::class)]
final class PlainTextDetectorTest extends UnitTestCase
{
    public static function plainTextDataProvider(): \Generator
    {
        yield 'tag-like word the parser does not close' => ['Ref <title> & co'];
        yield 'less-than sign before a letter' => ['a<b und x <y'];
        yield 'end tag that is none' => ['I </3 you'];
        yield 'bogus comment' => ['Use <!x for that'];
        yield 'processing instruction' => ['Use <?y for that'];
        yield 'element without end tag' => ['Use <b> for bold'];
        yield 'unknown element without end tag' => ['Press <Enter> to continue'];
        yield 'list items without end tags' => ['<ul><li>a<li>b</ul>'];
        yield 'markup error next to an ampersand' => ['Q&A <title> & co'];
    }

    #[Test]
    #[DataProvider('plainTextDataProvider')]
    public function isPlainTextReturnsTrueForContentTheHtmlParserWouldChange(string $content): void
    {
        $this->assertTrue((new PlainTextDetector())->isPlainText($content));
    }

    public static function htmlDataProvider(): \Generator
    {
        yield 'empty content' => [''];
        yield 'text without tags' => ['Fish & Chips for Tom &amp; Jerry'];
        yield 'less-than signs not starting a tag' => ['Kinder < 12 & "Teens" <3 <= 4'];
        yield 'rich text' => ['<p>Hello <strong>world</strong> &amp; <a href="t3://page?uid=1" title="a &gt; b">more</a></p>'];
        yield 'void elements' => ['<p>a<br>b<br />c</p><hr><img src="x.jpg" alt="">'];
        yield 'uppercase tags' => ['<P>Text</P>'];
        yield 'comment' => ['<!-- note --><p>Text</p>'];
        yield 'self-closed foreign element' => ['<p><svg viewBox="0 0 1 1"><path d="M0 0"/></svg></p>'];
        yield 'element with namespace prefix' => ['<p>a<o:p></o:p></p>'];
        // masterminds/html5 reports these as errors, but keeps every "&" it cannot decode as written.
        yield 'ampersand before a word that is no entity' => ['<p>Q&A</p>'];
        yield 'ampersand in a company name' => ['<p>AT&T&reg;</p>'];
        yield 'entity without semicolon' => ['<p>&copy 2026</p>'];
        yield 'numeric references without semicolon or digits' => ['<p>&#12 and &#x; and &;</p>'];
        yield 'ampersand in a link' => ['<p><a href="?id=1&lang=de">Link</a></p>'];
    }

    #[Test]
    #[DataProvider('htmlDataProvider')]
    public function isPlainTextReturnsFalseForHtml(string $content): void
    {
        $this->assertFalse((new PlainTextDetector())->isPlainText($content));
    }
}
