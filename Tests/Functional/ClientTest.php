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
use WebVision\Deepltranslate\Core\TranslatorInterface;

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
        $client = $this->get(TranslatorInterface::class);
        $response = $client->translate(
            $translateContent,
            'EN',
            'DE'
        );

        $this->assertInstanceOf(TextResult::class, $response);
        $this->assertSame(self::EXAMPLE_TEXT['de'], $response->text);
    }

    #[Test]
    public function checkResponseFromSupportedTargetLanguage(): void
    {
        $client = $this->get(TranslatorInterface::class);
        $response = $client->getSupportedLanguageByType();

        $this->assertIsArray($response);
        $this->assertContainsOnlyInstancesOf(Language::class, $response);
    }

    #[Test]
    public function checkResponseFromGlossaryLanguagePairs(): void
    {
        $client = $this->get(TranslatorInterface::class);
        $response = $client->getGlossaryLanguagePairs();

        $this->assertIsArray($response);
        $this->assertContainsOnlyInstancesOf(GlossaryLanguagePair::class, $response);
    }

    #[Test]
    public function checkResponseFromCreateGlossary(): void
    {
        $client = $this->get(TranslatorInterface::class);
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

        $this->assertInstanceOf(GlossaryInfo::class, $response);
        $this->assertSame(1, $response->entryCount);
        $this->assertIsString($response->glossaryId);
        $this->assertInstanceOf(DateTime::class, $response->creationTime);
    }

    #[Test]
    public function checkResponseGetAllGlossaries(): void
    {
        $client = $this->get(TranslatorInterface::class);
        $response = $client->getAllGlossaries();

        $this->assertIsArray($response);
        $this->assertContainsOnlyInstancesOf(GlossaryInfo::class, $response);
    }

    #[Test]
    public function checkResponseFromGetGlossary(): void
    {
        $client = $this->get(TranslatorInterface::class);
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

        $this->assertInstanceOf(GlossaryInfo::class, $response);
        $this->assertSame($glossary->glossaryId, $response->glossaryId);
        $this->assertSame(1, $response->entryCount);
    }

    #[Test]
    public function checkGlossaryDeletedNotCatchable(): void
    {
        $client = $this->get(TranslatorInterface::class);
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

        $this->assertNull($client->getGlossary($glossaryId));
    }

    #[Test]
    public function checkResponseFromGetGlossaryEntries(): void
    {
        $client = $this->get(TranslatorInterface::class);
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

        $this->assertInstanceOf(GlossaryEntries::class, $response);
        $this->assertSame(1, count($response->getEntries()));
    }
}
