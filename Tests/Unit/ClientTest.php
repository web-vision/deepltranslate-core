<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit;

use Closure;
use DeepL\TextResult;
use DeepL\Translator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WebVision\Deepltranslate\Core\Client;
use WebVision\Deepltranslate\Core\ConfigurationInterface;
use WebVision\Deepltranslate\Core\Domain\Dto\TranslatedTextResult;
use WebVision\Deepltranslate\Core\Exception\ApiKeyNotSetException;
use WebVision\Deepltranslate\Core\Service\HtmlXmlConverter;
use WebVision\Deepltranslate\Core\Service\LostLink;

class ClientTest extends UnitTestCase
{

    private function createMockConfigurationWithEmptyApiKey(): MockObject
    {
        $mockConfiguration = $this->getMockBuilder(ConfigurationInterface::class)
            ->getMock();

        $mockConfiguration
            ->method('getApiKey')
            ->willReturn('');

        return $mockConfiguration;
    }

    #[Test]
    public function throwErrorGetSupportedLanguageByTypeWhenApiKeyNotSet(): void
    {
        /** @var ConfigurationInterface $configurationMock */
        $configurationMock = $this->createMockConfigurationWithEmptyApiKey();
        $client = new Client($configurationMock);

        static::expectException(ApiKeyNotSetException::class);
        static::expectExceptionCode(1708081233823);
        static::expectExceptionMessage('The api key is not set');

        $client->getSupportedLanguageByType();
    }

    #[Test]
    public function throwErrorGetGlossaryLanguagePairsWhenApiKeyNotSet(): void
    {
        /** @var ConfigurationInterface $configurationMock */
        $configurationMock = $this->createMockConfigurationWithEmptyApiKey();
        $client = new Client($configurationMock);

        static::expectException(ApiKeyNotSetException::class);
        static::expectExceptionCode(1708081233823);
        static::expectExceptionMessage('The api key is not set');

        $client->getGlossaryLanguagePairs();
    }

    #[Test]
    public function throwErrorCreateGlossaryEntriesWhenApiKeyNotSet(): void
    {
        /** @var ConfigurationInterface $configurationMock */
        $configurationMock = $this->createMockConfigurationWithEmptyApiKey();
        $client = new Client($configurationMock);

        static::expectException(ApiKeyNotSetException::class);
        static::expectExceptionCode(1708081233823);
        static::expectExceptionMessage('The api key is not set');

        $response = $client->createGlossary(
            'Deepl-Client-Create-Function-Test:' . __FUNCTION__,
            'de',
            'en',
            [
                0 => [
                    'source' => 'hallo Welt',
                    'target' => 'hello world',
                ],
            ],
        );
    }

    #[Test]
    public function throwErrorGetGlossaryWhenApiKeyNotSet(): void
    {
        /** @var ConfigurationInterface $configurationMock */
        $configurationMock = $this->createMockConfigurationWithEmptyApiKey();
        $client = new Client($configurationMock);

        static::expectException(ApiKeyNotSetException::class);
        static::expectExceptionCode(1708081233823);
        static::expectExceptionMessage('The api key is not set');

        $response = $client->getGlossary('61567955-8db8-493d-aa20-28bbba6fb438');
    }

    #[Test]
    public function throwErrorDeletedGlossaryWhenApiKeyNotSet(): void
    {
        /** @var ConfigurationInterface $configurationMock */
        $configurationMock = $this->createMockConfigurationWithEmptyApiKey();
        $client = new Client($configurationMock);

        static::expectException(ApiKeyNotSetException::class);
        static::expectExceptionCode(1708081233823);
        static::expectExceptionMessage('The api key is not set');

        $client->deleteGlossary('25d90db6-bcab-4130-ab36-4514dd5d87ec');
    }

    #[Test]
    public function throwErrorGlossaryEntriesWhenApiKeyNotSet(): void
    {
        /** @var ConfigurationInterface $configurationMock */
        $configurationMock = $this->createMockConfigurationWithEmptyApiKey();
        $client = new Client($configurationMock);

        static::expectException(ApiKeyNotSetException::class);
        static::expectExceptionCode(1708081233823);
        static::expectExceptionMessage('The api key is not set');

        $response = $client->getGlossaryEntries('a44703d5-ece7-4230-a67b-1a07153768d6');
    }

    #[Test]
    public function throwErrorTranslationExceptionWhenApiKeyNotSet(): void
    {
        /** @var ConfigurationInterface $configurationMock */
        $configurationMock = $this->createMockConfigurationWithEmptyApiKey();
        $client = new Client($configurationMock);

        static::expectException(ApiKeyNotSetException::class);
        static::expectExceptionCode(1708081233823);
        static::expectExceptionMessage('The api key is not set');

        $client->translate(
            'proton beam',
            'DE',
            'EN'
        );
    }

    #[Test]
    public function translateSendsContentAsXmlWithSplittingTagsAndReturnsHtml(): void
    {
        $translator = $this->createMock(Translator::class);
        $translator->expects(static::once())
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
        $client = new Client($this->createMock(ConfigurationInterface::class), new HtmlXmlConverter());
        $client->setLogger(new NullLogger());
        Closure::bind(
            function (Translator $translator): void {
                $this->translator = $translator;
            },
            $client,
            Client::class
        )->call($client, $translator);

        $result = $client->translate('<p>Postanschrift:<br>Postfach 1234</p>', 'DE', 'ES', 'glossary-id', 'prefer_less');

        static::assertInstanceOf(TextResult::class, $result);
        static::assertSame('<p>Dirección postal:<br />Apartado de correos 1234</p>', $result->text);
    }

    /**
     * Issue #427: DeepL translates no attribute values, the title of a link is sent as a text of its own.
     */
    #[Test]
    public function translateSendsAttributeTextsInTheSameRequestAndBillsThem(): void
    {
        $translator = $this->createMock(Translator::class);
        $translator->expects(static::once())
            ->method('translateText')
            ->with(
                [
                    '<p>Test auf <a href="t3://page?uid=1736" title="Deutscher Titel &amp; mehr" dlt-r="0">Deutsch</a></p>',
                    'Deutscher Titel &amp; mehr',
                ],
                'DE',
                'EN-GB',
                static::isType('array'),
            )
            ->willReturn([
                new TextResult('<p>Test in <a href="t3://page?uid=1736" title="Deutscher Titel &amp; mehr" dlt-r="0">German</a></p>', 'DE', 22),
                new TextResult('German title &amp; more', 'DE', 22),
            ]);
        $client = $this->createClientWithTranslator($translator);

        $result = $client->translate('<p>Test auf <a href="t3://page?uid=1736" title="Deutscher Titel &amp; mehr">Deutsch</a></p>', 'DE', 'EN-GB');

        static::assertInstanceOf(TranslatedTextResult::class, $result);
        static::assertSame('<p>Test in <a href="t3://page?uid=1736" title="German title &amp; more">German</a></p>', $result->text);
        static::assertSame(44, $result->billedCharacters);
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
        $translator = $this->createMock(Translator::class);
        $translator->expects(static::exactly(2))->method('translateText')->willReturnCallback(
            static function (array $texts) use (&$requests): array {
                $requests[] = $texts;
                return array_map(static fn (string $text): TextResult => new TextResult(str_replace('Title', 'Titel', $text), 'EN', 1), $texts);
            }
        );
        $client = $this->createClientWithTranslator($translator);

        $result = $client->translate('<p>' . $links . '</p>', 'EN', 'DE');

        static::assertSame([50, 11], array_map(count(...), $requests));
        static::assertStringStartsWith('<p>', $requests[0][0]);
        static::assertSame('Title 50', $requests[1][0]);
        static::assertInstanceOf(TranslatedTextResult::class, $result);
        static::assertSame(61, $result->billedCharacters);
        static::assertStringContainsString('<a href="#1" title="Titel 1">1</a>', $result->text);
        static::assertStringContainsString('<a href="#60" title="Titel 60">60</a>', $result->text);
    }

    private function createClientWithTranslator(Translator $translator): Client
    {
        $client = new Client($this->createMock(ConfigurationInterface::class), new HtmlXmlConverter());
        $client->setLogger(new NullLogger());
        Closure::bind(
            function (Translator $translator): void {
                $this->translator = $translator;
            },
            $client,
            Client::class
        )->call($client, $translator);
        return $client;
    }

    #[Test]
    public function translateReturnsLostLinksAndLogsThem(): void
    {
        $translator = $this->createMock(Translator::class);
        $translator->method('translateText')->willReturn(
            new TextResult('<p>This is the <em dlt-r="0">extended</em> warranty for your bicycle.</p>', 'DE', 51)
        );
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(static::once())->method('warning')->with(
            static::stringContains('A link of the source content is missing'),
            ['targetLanguage' => 'EN-GB', 'href' => 't3://page?uid=5', 'text' => 'rad']
        );
        $client = new Client($this->createMock(ConfigurationInterface::class), new HtmlXmlConverter());
        $client->setLogger($logger);
        Closure::bind(
            function (Translator $translator): void {
                $this->translator = $translator;
            },
            $client,
            Client::class
        )->call($client, $translator);

        $result = $client->translate('<p>Das ist die Garantie<em>verlängerung</em> für Ihr Fahr<a href="t3://page?uid=5">rad</a>.</p>', 'DE', 'EN-GB');

        static::assertInstanceOf(TranslatedTextResult::class, $result);
        static::assertSame('<p>This is the <em>extended</em> warranty for your bicycle.</p>', $result->text);
        static::assertSame(51, $result->billedCharacters);
        static::assertEquals([new LostLink('t3://page?uid=5', 'rad')], $result->lostLinks);
    }

    #[Test]
    public function translateReturnsNullIfDeepLReturnsMalformedXml(): void
    {
        $translator = $this->createMock(Translator::class);
        $translator->method('translateText')->willReturn(new TextResult('<p>kaputt', 'DE', 8));
        $client = new Client($this->createMock(ConfigurationInterface::class), new HtmlXmlConverter());
        $client->setLogger(new NullLogger());
        Closure::bind(
            function (Translator $translator): void {
                $this->translator = $translator;
            },
            $client,
            Client::class
        )->call($client, $translator);

        static::assertNull($client->translate('<p>kaputt</p>', 'DE', 'EN-GB'));
    }
}
