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
            'expected' => '<p>Nutze &lt;b&gt; für fett &amp; mehr</p>',
        ];
        yield 'rich text keeps quotes inside attributes escaped' => [
            'contentFormat' => ContentFormat::RichText,
            'content' => '<p><a href="t3://page?uid=1&amp;x=2" title="Mehr &quot;erfahren&quot;">Link</a> und&nbsp;mehr</p>',
            'expected' => '<p><a href="t3://page?uid=1&amp;x=2" title="Mehr &quot;erfahren&quot;">Link</a> und&nbsp;mehr</p>',
        ];
        yield 'plain text with markup characters stays literal' => [
            'contentFormat' => ContentFormat::PlainText,
            'content' => 'Kinder < 12 & "Eltern" nutzen <b> für \'fett\'',
            'expected' => 'Kinder < 12 & "Eltern" nutzen <b> für \'fett\'',
        ];
        yield 'plain text with entity-like text and non-breaking space stays literal' => [
            'contentFormat' => ContentFormat::PlainText,
            'content' => "Tom &amp; Jerry\u{00A0}GmbH &nbsp;",
            'expected' => "Tom &amp; Jerry\u{00A0}GmbH &nbsp;",
        ];
        yield 'content of unknown format is decoded as before' => [
            'contentFormat' => ContentFormat::Unknown,
            'content' => 'Preise & Leistungen <b>fett</b>',
            'expected' => 'Preise & Leistungen <b>fett</b>',
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
        string $expected,
    ): void {
        $client = $this->createMock(DeepLClientInterface::class);
        $client->method('translateText')->willReturnCallback(
            static fn(string $text): TextResult => new TextResult($text, 'DE', mb_strlen($text))
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
    }

    #[Test]
    public function translateContextDefaultsToUnknownContentFormat(): void
    {
        $this->assertSame(ContentFormat::Unknown, (new TranslateContext('Text'))->getContentFormat());
    }
}
