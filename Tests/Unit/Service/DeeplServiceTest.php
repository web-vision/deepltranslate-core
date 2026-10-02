<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Service;

use DeepL\TextResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WebVision\Deepltranslate\Core\Client\DeepLClientFactoryInterface;
use WebVision\Deepltranslate\Core\Client\DeepLClientInterface;
use WebVision\Deepltranslate\Core\Domain\Dto\TranslateContext;
use WebVision\Deepltranslate\Core\Domain\Enum\ContentFormat;
use WebVision\Deepltranslate\Core\Event\DeepLContextEvent;
use WebVision\Deepltranslate\Core\Service\DeepLContextResolver;
use WebVision\Deepltranslate\Core\Service\DeeplService;
use WebVision\Deepltranslate\Core\Service\HtmlXmlConverter;
use WebVision\Deepltranslate\Core\Service\LostLink;
use WebVision\Deepltranslate\Core\Service\ProcessingInstruction;
use WebVision\Deepltranslate\Core\Translator;
use WebVision\Deepltranslate\Core\TranslatorInterface;

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
            // The title is sent as a text of its own, see Translator::translate().
            'expectedSent' => [
                "<p><a href=\"t3://page?uid=1&amp;x=2\" title=\"Mehr &quot;erfahren&quot;\" dlt-r=\"0\">Link</a> und\u{00A0}mehr</p>",
                'Mehr "erfahren"',
            ],
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
     *
     * @param string|list<string>|null $expectedSent the texts sent to DeepL, `null` for none
     */
    #[Test]
    #[DataProvider('translateContentDataProvider')]
    public function translateContentReturnsContentInTheFormatOfTheField(
        ContentFormat $contentFormat,
        string $content,
        string|array|null $expectedSent,
        string $expected,
    ): void {
        $sent = [];
        $client = $this->createMock(DeepLClientInterface::class);
        $client->expects($expectedSent === null ? $this->never() : $this->once())->method('translateText')->willReturnCallback(
            static function (string|array $text) use (&$sent): TextResult|array {
                $texts = (array)$text;
                $sent = [...$sent, ...$texts];
                $results = array_map(static fn(string $text): TextResult => new TextResult($text, 'DE', mb_strlen($text)), $texts);
                return is_array($text) ? $results : $results[0];
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
            $this->createEventDispatcher(),
            new NullLogger(),
            new DeepLContextResolver($this->createMock(SiteFinder::class)),
        );
        $translateContext = new TranslateContext($content);
        $translateContext->setSourceLanguageCode('auto');
        $translateContext->setTargetLanguageCode('EN-GB');
        $translateContext->setContentFormat($contentFormat);

        $this->assertSame($expected, $subject->translateContent($translateContext));
        $this->assertSame((array)$expectedSent, $sent);
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
            $this->createEventDispatcher(),
            new NullLogger(),
            new DeepLContextResolver($this->createMock(SiteFinder::class)),
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

    /**
     * A context set by the caller is sent as it is, the page and the site are not asked for one.
     */
    #[Test]
    public function translateContentSendsTheContextOfTheCaller(): void
    {
        $sentContexts = [];
        $events = [];
        $subject = $this->createSubjectSendingContexts($sentContexts, static function (object $event) use (&$events): object {
            $events[] = $event;
            return $event;
        });
        $translateContext = new TranslateContext('<p>The court is closed on Sundays.</p>');
        $translateContext->setSourceLanguageCode('EN');
        $translateContext->setTargetLanguageCode('DE');
        $translateContext->setContentFormat(ContentFormat::RichText);
        $translateContext->setContext('The website of a tennis club.');

        $subject->translateContent($translateContext);

        $this->assertSame(['The website of a tennis club.'], $sentContexts);
        $contextEvents = array_values(array_filter($events, static fn(object $event): bool => $event instanceof DeepLContextEvent));
        $this->assertCount(1, $contextEvents);
        $this->assertSame('The website of a tennis club.', $contextEvents[0]->context);
        $this->assertSame('EN', $contextEvents[0]->sourceLanguage);
        $this->assertSame('DE', $contextEvents[0]->targetLanguage);
        $this->assertNull($contextEvents[0]->currentPage);
    }

    /**
     * @return iterable<string, array{string, string, string|null}>
     */
    public static function contextChangedByEventDataProvider(): iterable
    {
        yield 'context replaced' => ['The website of a tennis club.', 'The website of a law firm.', 'The website of a law firm.'];
        yield 'context added' => ['', 'The website of a law firm.', 'The website of a law firm.'];
        yield 'context removed' => ['The website of a tennis club.', '', null];
        yield 'context of whitespace only removed' => ['The website of a tennis club.', "  \n ", null];
    }

    #[Test]
    #[DataProvider('contextChangedByEventDataProvider')]
    public function translateContentSendsTheContextChangedByTheEvent(string $context, string $changedContext, ?string $expectedSent): void
    {
        $sentContexts = [];
        $subject = $this->createSubjectSendingContexts($sentContexts, static function (object $event) use ($changedContext): object {
            if ($event instanceof DeepLContextEvent) {
                $event->context = $changedContext;
            }
            return $event;
        });
        $translateContext = new TranslateContext('<p>The court is closed on Sundays.</p>');
        $translateContext->setSourceLanguageCode('auto');
        $translateContext->setTargetLanguageCode('DE');
        $translateContext->setContentFormat(ContentFormat::RichText);
        $translateContext->setContext($context);

        $subject->translateContent($translateContext);

        $this->assertSame([$expectedSent], $sentContexts);
        $this->assertSame(trim($changedContext), $translateContext->getContext());
    }

    /**
     * A translator implementing only TranslatorInterface, written before the context existed, is called as before.
     */
    #[Test]
    public function translateContentCallsATranslatorWithoutContextSupportWithoutContext(): void
    {
        $translator = new class (new NullLogger(), $this->createMock(DeepLClientFactoryInterface::class)) implements TranslatorInterface {
            /**
             * @var list<list<mixed>>
             */
            public array $calls = [];

            public function __construct(LoggerInterface $logger, DeepLClientFactoryInterface $clientFactory) {}

            public function translate(string $content, ?string $sourceLang, string $targetLang, string $glossary = '', string $formality = ''): TextResult
            {
                $this->calls[] = func_get_args();
                return new TextResult($content, 'EN', mb_strlen($content));
            }

            public function getSupportedLanguageByType(string $type = 'target'): array
            {
                return [];
            }
        };
        $runtimeCache = $this->createMock(FrontendInterface::class);
        $runtimeCache->method('has')->willReturn(true);
        $runtimeCache->method('get')->willReturn(['tableName' => null, 'id' => null, 'deeplMode' => true]);
        $subject = new DeeplService(
            $this->createMock(FrontendInterface::class),
            $translator,
            new ProcessingInstruction($runtimeCache),
            $this->createEventDispatcher(),
            new NullLogger(),
            new DeepLContextResolver($this->createMock(SiteFinder::class)),
        );
        $translateContext = new TranslateContext('<p>The court is closed on Sundays.</p>');
        $translateContext->setSourceLanguageCode('EN');
        $translateContext->setTargetLanguageCode('DE');
        $translateContext->setContentFormat(ContentFormat::RichText);
        $translateContext->setContext('The website of a tennis club.');

        $this->assertSame('<p>The court is closed on Sundays.</p>', $subject->translateContent($translateContext));
        $this->assertSame([['<p>The court is closed on Sundays.</p>', 'EN', 'DE', '', 'default']], $translator->calls);
    }

    /**
     * @param list<string|null> $sentContexts the `context` option of each request, `null` if it is not sent
     * @param \Closure(object): object $dispatch
     */
    private function createSubjectSendingContexts(array &$sentContexts, \Closure $dispatch): DeeplService
    {
        $client = $this->createMock(DeepLClientInterface::class);
        $client->method('translateText')->willReturnCallback(
            static function (string|array $text, ?string $sourceLang, string $targetLang, array $options) use (&$sentContexts): TextResult|array {
                $sentContexts[] = $options['context'] ?? null;
                $results = array_map(static fn(string $text): TextResult => new TextResult($text, 'EN', mb_strlen($text)), (array)$text);
                return is_array($text) ? $results : $results[0];
            }
        );
        $clientFactory = $this->createMock(DeepLClientFactoryInterface::class);
        $clientFactory->method('create')->willReturn($client);
        $runtimeCache = $this->createMock(FrontendInterface::class);
        $runtimeCache->method('has')->willReturn(true);
        $runtimeCache->method('get')->willReturn(['tableName' => null, 'id' => null, 'deeplMode' => true]);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->method('dispatch')->willReturnCallback($dispatch);
        // Without a page of the record the resolver is not asked, so its site finder is never used.
        $siteFinder = $this->createMock(SiteFinder::class);
        $siteFinder->expects($this->never())->method($this->anything());
        return new DeeplService(
            $this->createMock(FrontendInterface::class),
            new Translator(new NullLogger(), $clientFactory, new HtmlXmlConverter()),
            new ProcessingInstruction($runtimeCache),
            $eventDispatcher,
            new NullLogger(),
            new DeepLContextResolver($siteFinder),
        );
    }

    private function createEventDispatcher(): EventDispatcher
    {
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->method('dispatch')->willReturnArgument(0);
        return $eventDispatcher;
    }
}
