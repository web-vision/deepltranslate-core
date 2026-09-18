<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit;

use DeepL\TextResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WebVision\Deepltranslate\Core\Client\DeepLClientFactoryInterface;
use WebVision\Deepltranslate\Core\Client\DeepLClientInterface;
use WebVision\Deepltranslate\Core\Service\HtmlXmlConverter;
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
        $this->assertSame('<p>Dirección postal:<br>Apartado de correos 1234</p>', $result->text);
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
