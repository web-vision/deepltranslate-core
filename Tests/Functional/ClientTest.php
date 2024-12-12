<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional;

use DateTime;
use DeepL\GlossaryEntries;
use DeepL\GlossaryInfo;
use DeepL\GlossaryLanguagePair;
use DeepL\Language;
use DeepL\TextResult;
use Helmich\JsonAssert\JsonAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use WebVision\Deepltranslate\Core\Client;
use WebVision\Deepltranslate\Core\ClientInterface;

#[CoversClass(Client::class)]
final class ClientTest extends AbstractDeepLTestCase
{
    use JsonAssertions;

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = array_merge(
            $this->configurationToUseInTestInstance,
            require __DIR__ . '/Fixtures/ExtensionConfig.php'
        );
        parent::setUp();
    }

    #[Test]
    public function checkResponseFromTranslateContent(): void
    {
        $translateContent = self::EXAMPLE_TEXT['en'];
        $client = $this->get(ClientInterface::class);
        $response = $client->translate(
            $translateContent,
            'EN',
            'DE'
        );

        static::assertInstanceOf(TextResult::class, $response);
        static::assertSame(self::EXAMPLE_TEXT['de'], $response->text);
    }

    #[Test]
    public function checkResponseFromSupportedTargetLanguage(): void
    {
        $client = $this->get(ClientInterface::class);
        $response = $client->getSupportedLanguageByType();

        static::assertIsArray($response);
        static::assertContainsOnlyInstancesOf(Language::class, $response);
    }

    #[Test]
    public function checkResponseFromGlossaryLanguagePairs(): void
    {
        $client = $this->get(ClientInterface::class);
        $response = $client->getGlossaryLanguagePairs();

        static::assertIsArray($response);
        static::assertContainsOnlyInstancesOf(GlossaryLanguagePair::class, $response);
    }

    #[Test]
    public function checkResponseFromCreateGlossary(): void
    {
        $client = $this->get(ClientInterface::class);
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

        static::assertInstanceOf(GlossaryInfo::class, $response);
        static::assertSame(1, $response->entryCount);
        static::assertIsString($response->glossaryId);
        static::assertInstanceOf(DateTime::class, $response->creationTime);
    }

    #[Test]
    public function checkResponseGetAllGlossaries(): void
    {
        $client = $this->get(ClientInterface::class);
        $response = $client->getAllGlossaries();

        static::assertIsArray($response);
        static::assertContainsOnlyInstancesOf(GlossaryInfo::class, $response);
    }

    #[Test]
    public function checkResponseFromGetGlossary(): void
    {
        $client = $this->get(ClientInterface::class);
        $glossary = $client->createGlossary(
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

        $response = $client->getGlossary($glossary->glossaryId);

        static::assertInstanceOf(GlossaryInfo::class, $response);
        static::assertSame($glossary->glossaryId, $response->glossaryId);
        static::assertSame(1, $response->entryCount);
    }

    #[Test]
    public function checkGlossaryDeletedNotCatchable(): void
    {
        $client = $this->get(ClientInterface::class);
        $glossary = $client->createGlossary(
            'Deepl-Client-Create-Function-Test' . __FUNCTION__,
            'de',
            'en',
            [
                0 => [
                    'source' => 'hallo Welt',
                    'target' => 'hello world',
                ],
            ],
        );

        $glossaryId = $glossary->glossaryId;

        $client->deleteGlossary($glossaryId);

        static::assertNull($client->getGlossary($glossaryId));
    }

    #[Test]
    public function checkResponseFromGetGlossaryEntries(): void
    {
        $client = $this->get(ClientInterface::class);
        $glossary = $client->createGlossary(
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

        $response = $client->getGlossaryEntries($glossary->glossaryId);

        static::assertInstanceOf(GlossaryEntries::class, $response);
        static::assertSame(1, count($response->getEntries()));
    }
}
