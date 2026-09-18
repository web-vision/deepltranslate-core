<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit;

use Closure;
use DeepL\TextResult;
use DeepL\Translator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WebVision\Deepltranslate\Core\Client;
use WebVision\Deepltranslate\Core\ConfigurationInterface;
use WebVision\Deepltranslate\Core\Exception\ApiKeyNotSetException;
use WebVision\Deepltranslate\Core\Service\HtmlXmlConverter;

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
        static::assertSame('<p>Dirección postal:<br>Apartado de correos 1234</p>', $result->text);
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
