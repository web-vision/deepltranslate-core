<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Service;

use DeepL\TextResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WebVision\Deepltranslate\Core\Client\DeepLClientFactoryInterface;
use WebVision\Deepltranslate\Core\Client\DeepLClientInterface;
use WebVision\Deepltranslate\Core\Domain\Dto\TranslateContext;
use WebVision\Deepltranslate\Core\Domain\Enum\ContentFormat;
use WebVision\Deepltranslate\Core\Service\DeeplService;
use WebVision\Deepltranslate\Core\Service\HtmlXmlConverter;
use WebVision\Deepltranslate\Core\Service\LostLink;
use WebVision\Deepltranslate\Core\Service\ProcessingInstruction;
use WebVision\Deepltranslate\Core\Translator;

#[CoversClass(DeeplService::class)]
final class DeeplServiceTest extends UnitTestCase
{
    public static function translateContentDataProvider(): \Generator
    {
        yield 'rich text keeps escaped markup in text' => [
            'contentFormat' => ContentFormat::RichText,
            'content' => '<p>Nutze &lt;b&gt; für fett &amp; mehr</p>',
            'expectedSent' => '<p>Nutze &lt;b&gt; für fett &amp; mehr</p>',
            'expected' => '<p>Nutze &lt;b&gt; für fett &amp; mehr</p>',
        ];
        yield 'rich text keeps quotes inside attributes escaped' => [
            'contentFormat' => ContentFormat::RichText,
            'content' => '<p><a href="t3://page?uid=1&amp;x=2" title="Mehr &quot;erfahren&quot;">Link</a> und&nbsp;mehr</p>',
            'expectedSent' => "<p><a href=\"t3://page?uid=1&amp;x=2\" title=\"Mehr &quot;erfahren&quot;\" dlt-r=\"0\">Link</a> und\u{00A0}mehr</p>",
            'expected' => '<p><a href="t3://page?uid=1&amp;x=2" title="Mehr &quot;erfahren&quot;">Link</a> und&nbsp;mehr</p>',
        ];
        yield 'plain text with markup characters stays literal' => [
            'contentFormat' => ContentFormat::PlainText,
            'content' => 'Kinder < 12 & "Eltern" nutzen <b> für \'fett\'',
            'expectedSent' => 'Kinder &lt; 12 &amp; "Eltern" nutzen &lt;b&gt; für \'fett\'',
            'expected' => 'Kinder < 12 & "Eltern" nutzen <b> für \'fett\'',
        ];
        yield 'plain text with entity-like text and non-breaking space stays literal' => [
            'contentFormat' => ContentFormat::PlainText,
            'content' => "Tom &amp; Jerry\u{00A0}GmbH &nbsp;",
            'expectedSent' => "Tom &amp;amp; Jerry\u{00A0}GmbH &amp;nbsp;",
            'expected' => "Tom &amp; Jerry\u{00A0}GmbH &nbsp;",
        ];
        yield 'content of unknown format is decoded as before' => [
            'contentFormat' => ContentFormat::Unknown,
            'content' => 'Preise & Leistungen <b>fett</b>',
            'expectedSent' => 'Preise &amp; Leistungen <b dlt-r="0">fett</b>',
            'expected' => 'Preise & Leistungen <b>fett</b>',
        ];
        yield 'content of unknown format with entities and non-breaking space is decoded' => [
            'contentFormat' => ContentFormat::Unknown,
            'content' => '<p>a&nbsp;b &lt;c&gt;</p>',
            'expectedSent' => "<p>a\u{00A0}b &lt;c&gt;</p>",
            'expected' => "<p>a\u{00A0}b <c></p>",
        ];
        yield 'content of unknown format with a lone non-breaking space' => [
            'contentFormat' => ContentFormat::Unknown,
            'content' => "\u{00A0}",
            'expectedSent' => "\u{00A0}",
            'expected' => "\u{00A0}",
        ];
        yield 'content of unknown format the HTML parser would change is plain text' => [
            'contentFormat' => ContentFormat::Unknown,
            'content' => 'Ref <title> & co',
            'expectedSent' => 'Ref &lt;title&gt; &amp; co',
            'expected' => 'Ref <title> & co',
        ];
        yield 'content of unknown format with an ampersand the HTML parser keeps is HTML' => [
            'contentFormat' => ContentFormat::Unknown,
            'content' => '<p>Q&A with <b>AT&T</b></p>',
            'expectedSent' => '<p>Q&amp;A with <b dlt-r="0">AT&amp;T</b></p>',
            'expected' => '<p>Q&A with <b>AT&T</b></p>',
        ];
        yield 'content of unknown format with a broken end tag is plain text' => [
            'contentFormat' => ContentFormat::Unknown,
            'content' => 'I </3 you & "me"',
            'expectedSent' => 'I &lt;/3 you &amp; "me"',
            'expected' => 'I </3 you & "me"',
        ];
        yield 'code is not sent' => [
            'contentFormat' => ContentFormat::Code,
            'content' => '<script>var label = "Opening hours"; if (a < b) {}</script>',
            'expectedSent' => null,
            'expected' => '<script>var label = "Opening hours"; if (a < b) {}</script>',
        ];
    }

    /**
     * DeepL is replaced by a client returning its input, so the test covers everything the
     * extension does to the content before and after the API call.
     */
    #[Test]
    #[DataProvider('translateContentDataProvider')]
    public function translateContentReturnsContentInTheFormatOfTheField(
        ContentFormat $contentFormat,
        string $content,
        ?string $expectedSent,
        string $expected,
    ): void {
        $sent = [];
        $client = $this->createMock(DeepLClientInterface::class);
        $client->expects($expectedSent === null ? $this->never() : $this->once())->method('translateText')->willReturnCallback(
            static function (string $text) use (&$sent): TextResult {
                $sent[] = $text;
                return new TextResult($text, 'DE', mb_strlen($text));
            }
        );
        $clientFactory = $this->createMock(DeepLClientFactoryInterface::class);
        $clientFactory->method('create')->willReturn($client);
        $runtimeCache = $this->createMock(FrontendInterface::class);
        $runtimeCache->method('has')->willReturn(true);
        $runtimeCache->method('get')->willReturn([
            'tableName' => null,
            'id' => null,
            'deeplMode' => true,
        ]);
        $subject = new DeeplService(
            $this->createMock(FrontendInterface::class),
            new Translator(new NullLogger(), $clientFactory, new HtmlXmlConverter()),
            new ProcessingInstruction($runtimeCache),
            $this->createMock(EventDispatcher::class),
            new NullLogger(),
        );
        $translateContext = new TranslateContext($content);
        $translateContext->setSourceLanguageCode('auto');
        $translateContext->setTargetLanguageCode('EN-GB');
        $translateContext->setContentFormat($contentFormat);

        $this->assertSame($expected, $subject->translateContent($translateContext));
        $this->assertSame($expectedSent === null ? [] : [$expectedSent], $sent);
    }

    #[Test]
    public function translateContentHandsTheLostLinksToTheContext(): void
    {
        $client = $this->createMock(DeepLClientInterface::class);
        $client->method('translateText')->willReturn(
            new TextResult('<p>This is the <em dlt-r="0">extended</em> warranty for your bicycle.</p>', 'DE', 51)
        );
        $clientFactory = $this->createMock(DeepLClientFactoryInterface::class);
        $clientFactory->method('create')->willReturn($client);
        $runtimeCache = $this->createMock(FrontendInterface::class);
        $runtimeCache->method('has')->willReturn(true);
        $runtimeCache->method('get')->willReturn(['tableName' => null, 'id' => null, 'deeplMode' => true]);
        $subject = new DeeplService(
            $this->createMock(FrontendInterface::class),
            new Translator(new NullLogger(), $clientFactory, new HtmlXmlConverter()),
            new ProcessingInstruction($runtimeCache),
            $this->createMock(EventDispatcher::class),
            new NullLogger(),
        );
        $translateContext = new TranslateContext('<p>Das ist die Garantie<em>verlängerung</em> für Ihr Fahr<a href="t3://page?uid=5">rad</a>.</p>');
        $translateContext->setSourceLanguageCode('auto');
        $translateContext->setTargetLanguageCode('EN-GB');
        $translateContext->setContentFormat(ContentFormat::RichText);

        $this->assertSame('<p>This is the <em>extended</em> warranty for your bicycle.</p>', $subject->translateContent($translateContext));
        $this->assertEquals([new LostLink('t3://page?uid=5', 'rad')], $translateContext->getLostLinks());
    }

    #[Test]
    public function translateContextDefaultsToUnknownContentFormat(): void
    {
        $this->assertSame(ContentFormat::Unknown, (new TranslateContext('Text'))->getContentFormat());
    }
}
