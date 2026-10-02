<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit;

use DeepL\TextResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WebVision\Deepltranslate\Core\Client\DeepLClientFactoryInterface;
use WebVision\Deepltranslate\Core\Client\DeepLClientInterface;
use WebVision\Deepltranslate\Core\Domain\Dto\TranslatedTextResult;
use WebVision\Deepltranslate\Core\Service\HtmlXmlConverter;
use WebVision\Deepltranslate\Core\Service\LostLink;
use WebVision\Deepltranslate\Core\Translator;

#[CoversClass(Translator::class)]
final class TranslatorTest extends UnitTestCase
{
    #[Test]
    public function translateSendsContentAsXmlWithSplittingTagsAndReturnsHtml(): void
    {
        $client = $this->createMock(DeepLClientInterface::class);
        $client->expects($this->once())
            ->method('translateText')
            ->with(
                '<p>Postanschrift:<br/>Postfach 1234</p>',
                'DE',
                'ES',
                [
                    'formality' => 'prefer_less',
                    'tag_handling' => 'xml',
                    'tag_handling_version' => 'v2',
                    'splitting_tags' => [
                        'br',
                        'p',
                        'div',
                        'li',
                        'dt',
                        'dd',
                        'h1',
                        'h2',
                        'h3',
                        'h4',
                        'h5',
                        'h6',
                        'td',
                        'th',
                        'caption',
                        'blockquote',
                        'figcaption',
                        'address',
                        'pre',
                        'dlt-s',
                    ],
                    'ignore_tags' => [
                        'script',
                        'style',
                    ],
                    'non_splitting_tags' => [
                        'a', 'abbr', 'b', 'bdi', 'bdo', 'cite', 'code', 'data', 'del', 'dfn', 'em', 'i', 'ins', 'kbd', 'mark', 'q',
                        's', 'samp', 'small', 'span', 'strong', 'sub', 'sup', 'time', 'u', 'var',
                        'dlt-p',
                    ],
                    'glossary' => 'glossary-id',
                ],
            )
            ->willReturn(new TextResult('<p>Dirección postal:<br/>Apartado de correos 1234</p>', 'DE', 58));
        $clientFactory = $this->createMock(DeepLClientFactoryInterface::class);
        $clientFactory->method('create')->willReturn($client);
        $subject = new Translator(new NullLogger(), $clientFactory, new HtmlXmlConverter());

        $result = $subject->translate('<p>Postanschrift:<br>Postfach 1234</p>', 'DE', 'ES', 'glossary-id', 'prefer_less');

        $this->assertInstanceOf(TextResult::class, $result);
        $this->assertSame('<p>Dirección postal:<br />Apartado de correos 1234</p>', $result->text);
    }

    /**
     * Issue #427: DeepL translates no attribute values, the title of a link is sent as a text of its own.
     */
    #[Test]
    public function translateSendsAttributeTextsInTheSameRequestAndBillsThem(): void
    {
        $client = $this->createMock(DeepLClientInterface::class);
        $client->expects($this->once())
            ->method('translateText')
            ->with(
                [
                    '<p>Test auf <a href="t3://page?uid=1736" title="Deutscher Titel &amp; mehr" dlt-r="0">Deutsch</a></p>',
                    'Deutscher Titel &amp; mehr',
                ],
                'DE',
                'EN-GB',
                $this->isType('array'),
            )
            ->willReturn([
                new TextResult('<p>Test in <a href="t3://page?uid=1736" title="Deutscher Titel &amp; mehr" dlt-r="0">German</a></p>', 'DE', 22),
                new TextResult('German title &amp; more', 'DE', 22),
            ]);
        $clientFactory = $this->createMock(DeepLClientFactoryInterface::class);
        $clientFactory->method('create')->willReturn($client);
        $subject = new Translator(new NullLogger(), $clientFactory, new HtmlXmlConverter());

        $result = $subject->translate('<p>Test auf <a href="t3://page?uid=1736" title="Deutscher Titel &amp; mehr">Deutsch</a></p>', 'DE', 'EN-GB');

        $this->assertInstanceOf(TranslatedTextResult::class, $result);
        $this->assertSame('<p>Test in <a href="t3://page?uid=1736" title="German title &amp; more">German</a></p>', $result->text);
        $this->assertSame(44, $result->billedCharacters);
    }

    /**
     * DeepL takes 50 texts in one request (https://developers.deepl.com/api-reference/translate), the content
     * and the first 49 attribute texts go into the first one.
     */
    #[Test]
    public function translateSendsMoreTextsThanOneRequestTakesInFurtherRequests(): void
    {
        $links = '';
        for ($number = 1; $number <= 60; $number++) {
            $links .= sprintf('<a href="#%1$d" title="Title %1$d">%1$d</a> ', $number);
        }
        $requests = [];
        $client = $this->createMock(DeepLClientInterface::class);
        $client->expects($this->exactly(2))->method('translateText')->willReturnCallback(
            static function (array $texts) use (&$requests): array {
                $requests[] = $texts;
                return array_map(static fn(string $text): TextResult => new TextResult(str_replace('Title', 'Titel', $text), 'EN', 1), $texts);
            }
        );
        $clientFactory = $this->createMock(DeepLClientFactoryInterface::class);
        $clientFactory->method('create')->willReturn($client);
        $subject = new Translator(new NullLogger(), $clientFactory, new HtmlXmlConverter());

        $result = $subject->translate('<p>' . $links . '</p>', 'EN', 'DE');

        $this->assertSame([50, 11], array_map(count(...), $requests));
        $this->assertStringStartsWith('<p>', $requests[0][0]);
        $this->assertSame('Title 50', $requests[1][0]);
        $this->assertInstanceOf(TranslatedTextResult::class, $result);
        $this->assertSame(61, $result->billedCharacters);
        $this->assertStringContainsString('<a href="#1" title="Titel 1">1</a>', $result->text);
        $this->assertStringContainsString('<a href="#60" title="Titel 60">60</a>', $result->text);
    }

    /**
     * Issue #666: the context goes with every request of the field, the one of further attribute texts included.
     * Without a context the option is not sent, see translateSendsContentAsXmlWithSplittingTagsAndReturnsHtml().
     */
    #[Test]
    public function translateSendsTheContextWithEveryRequest(): void
    {
        $links = '';
        for ($number = 1; $number <= 60; $number++) {
            $links .= sprintf('<a href="#%1$d" title="Court %1$d">%1$d</a> ', $number);
        }
        $contexts = [];
        $client = $this->createMock(DeepLClientInterface::class);
        $client->expects($this->exactly(2))->method('translateText')->willReturnCallback(
            static function (array $texts, ?string $sourceLang, string $targetLang, array $options) use (&$contexts): array {
                $contexts[] = $options['context'] ?? null;
                return array_map(static fn(string $text): TextResult => new TextResult($text, 'EN', 1), $texts);
            }
        );
        $clientFactory = $this->createMock(DeepLClientFactoryInterface::class);
        $clientFactory->method('create')->willReturn($client);
        $subject = new Translator(new NullLogger(), $clientFactory, new HtmlXmlConverter());

        $subject->translate('<p>' . $links . '</p>', 'EN', 'DE', '', '', 'The website of a tennis club.');

        $this->assertSame(['The website of a tennis club.', 'The website of a tennis club.'], $contexts);
    }

    #[Test]
    public function translateReturnsLostLinksAndLogsThem(): void
    {
        $client = $this->createMock(DeepLClientInterface::class);
        $client->method('translateText')->willReturn(
            new TextResult('<p>This is the <em dlt-r="0">extended</em> warranty for your bicycle.</p>', 'DE', 51)
        );
        $clientFactory = $this->createMock(DeepLClientFactoryInterface::class);
        $clientFactory->method('create')->willReturn($client);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with(
            $this->stringContains('A link of the source content is missing'),
            ['targetLanguage' => 'EN-GB', 'href' => 't3://page?uid=5', 'text' => 'rad']
        );
        $subject = new Translator($logger, $clientFactory, new HtmlXmlConverter());

        $result = $subject->translate('<p>Das ist die Garantie<em>verlängerung</em> für Ihr Fahr<a href="t3://page?uid=5">rad</a>.</p>', 'DE', 'EN-GB');

        $this->assertInstanceOf(TranslatedTextResult::class, $result);
        $this->assertSame('<p>This is the <em>extended</em> warranty for your bicycle.</p>', $result->text);
        $this->assertSame(51, $result->billedCharacters);
        $this->assertEquals([new LostLink('t3://page?uid=5', 'rad')], $result->lostLinks);
    }

    #[Test]
    public function translateReturnsNullIfDeepLReturnsMalformedXml(): void
    {
        $client = $this->createMock(DeepLClientInterface::class);
        $client->method('translateText')->willReturn(new TextResult('<p>kaputt', 'DE', 8));
        $clientFactory = $this->createMock(DeepLClientFactoryInterface::class);
        $clientFactory->method('create')->willReturn($client);
        $subject = new Translator(new NullLogger(), $clientFactory, new HtmlXmlConverter());

        $this->assertNull($subject->translate('<p>kaputt</p>', 'DE', 'EN-GB'));
    }
}
