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
