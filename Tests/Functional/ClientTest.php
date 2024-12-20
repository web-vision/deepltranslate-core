<?php

declare(strict_types=1);

namespace WebVision\WvDeepltranslate\Tests\Functional;

use Helmich\JsonAssert\JsonAssertions;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use PHPUnit\Framework\Constraint\IsType;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\WvDeepltranslate\Client;
use WebVision\WvDeepltranslate\Configuration;
use WebVision\WvDeepltranslate\Domain\Dto\TranslateOptions;

/**
 * @covers \WebVision\WvDeepltranslate\Client
 */
class ClientTest extends FunctionalTestCase
{
    use JsonAssertions;

    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/wv_deepltranslate',
    ];

    /**
     * @var string[]
     */
    private array $glossaryIdStorage;

    public function __construct(...$arguments)
    {
        parent::__construct(...$arguments);
        $this->configurationToUseInTestInstance = array_merge(
            $this->configurationToUseInTestInstance,
            require __DIR__ . '/Fixtures/ExtensionConfig.php'
        );
    }

    /**
     * @test
     */
    public function checkResponseFromTranslateContent(): void
    {
        $translateContent = 'I would like to be translated!';
        if (defined('DEEPL_MOCKSERVER_USED') && DEEPL_MOCKSERVER_USED === true) {
            $translateContent = 'proton beam';
        }

        $translateOptions = new TranslateOptions();
        $translateOptions->setSourceLanguage('EN');
        $translateOptions->setTargetLanguage('DE');

        $client = new Client();
        $response = $client->translate(
            $translateContent,
            $translateOptions,
        );

        static::assertSame(200, $response->getStatusCode());
        $content = $response->getBody()->getContents();
        static::assertJson($content);
    }

    /**
     * @test
     */
    public function checkJsonTranslateContentIsValid(): void
    {
        $translateContent = 'I would like to be translated!';
        $expectedTranslation = 'Ich möchte gerne übersetzt werden!';
        if (defined('DEEPL_MOCKSERVER_USED') && DEEPL_MOCKSERVER_USED === true) {
            $translateContent = 'proton beam';
            $expectedTranslation = 'Protonenstrahl';
        }

        $translateOptions = new TranslateOptions();
        $translateOptions->setSourceLanguage('EN');
        $translateOptions->setTargetLanguage('DE');

        $client = new Client();
        $response = $client->translate(
            $translateContent,
            $translateOptions,
        );

        $content = $response->getBody()->getContents();
        $jsonObject = json_decode($content);

        static::assertJsonDocumentMatches($jsonObject, [
            '$.translations' => new IsType(IsType::TYPE_ARRAY),
            '$.translations[*].text' => new IsType(IsType::TYPE_STRING),
        ]);

        static::assertJsonValueEquals(
            $jsonObject,
            '$.translations[*].text',
            $expectedTranslation
        );
    }

    /**
     * @test
     */
    public function checkResponseFromSupportedTargetLanguage(): void
    {
        $client = new Client();
        $response = $client->getSupportedTargetLanguage();

        static::assertSame(200, $response->getStatusCode());
        $content = $response->getBody()->getContents();
        static::assertJson($content);
    }

    /**
     * @test
     */
    public function checkJsonFromSupportedTargetLanguageIsValid(): void
    {
        $client = new Client();
        $response = $client->getSupportedTargetLanguage();

        $content = $response->getBody()->getContents();
        $jsonObject = json_decode($content);

        static::assertJsonDocumentMatches($jsonObject, [
            '$.' => new IsType(IsType::TYPE_ARRAY),
            '$.[*].language' => new IsType(IsType::TYPE_STRING),
        ]);
        static::assertJsonValueEquals($jsonObject, '$.[*].language', 'EN-GB');
        static::assertJsonValueEquals($jsonObject, '$.[*].language', 'EN-US');
        static::assertJsonValueEquals($jsonObject, '$.[*].language', 'DE');
        static::assertJsonValueEquals($jsonObject, '$.[*].language', 'UK');
    }

    /**
     * @test
     */
    public function checkResponseFromGlossaryLanguagePairs(): void
    {
        $client = new Client();
        $response = $client->getGlossaryLanguagePairs();

        static::assertSame(200, $response->getStatusCode());
        $content = $response->getBody()->getContents();
        static::assertJson($content);
    }

    /**
     * @test
     */
    public function checkJsonFromGlossaryLanguagePairsIsValid(): void
    {
        $client = new Client();
        $response = $client->getGlossaryLanguagePairs();

        $content = $response->getBody()->getContents();
        $jsonObject = json_decode($content);

        static::assertJsonDocumentMatches($jsonObject, [
            '$.supported_languages' => new IsType(IsType::TYPE_ARRAY),
            '$.supported_languages[*].source_lang' => new IsType(IsType::TYPE_STRING),
            '$.supported_languages[*].target_lang' => new IsType(IsType::TYPE_STRING),
        ]);
    }

    /**
     * @test
     */
    public function checkResponseFromCreateGlossary(): void
    {
        $client = new Client();
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

        static::assertSame(201, $response->getStatusCode());
        $content = $response->getBody()->getContents();
        static::assertJson($content);

        $jsonObject = json_decode($content, true);
        $this->glossaryIdStorage[] = $jsonObject['glossary_id'];
    }

    /**
     * @test
     */
    public function checkJsonFromCreateGlossaryIsValid(): void
    {
        $client = new Client();
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

        $content = $response->getBody()->getContents();
        $jsonObject = json_decode($content, true);
        $this->glossaryIdStorage[] = $jsonObject['glossary_id'];

        static::assertJsonDocumentMatches($jsonObject, [
            '$.glossary_id' => new IsType(IsType::TYPE_STRING),
            '$.ready' => new IsType(IsType::TYPE_BOOL),
            '$.name' => new IsType(IsType::TYPE_STRING),
            '$.source_lang' => new IsType(IsType::TYPE_STRING),
            '$.target_lang' => new IsType(IsType::TYPE_STRING),
            '$.entry_count' => new IsType(IsType::TYPE_INT),
        ]);

        static::assertJsonValueEquals($jsonObject, '$.name', 'Deepl-Client-Create-Function-Test:' . __FUNCTION__);
        static::assertJsonValueEquals($jsonObject, '$.source_lang', 'de');
        static::assertJsonValueEquals($jsonObject, '$.target_lang', 'en');
    }

    /**
     * @test
     */
    public function checkResponseGetAllGlossaries(): void
    {
        $client = new Client();
        $response = $client->getAllGlossaries();

        static::assertSame(200, $response->getStatusCode());
        $content = $response->getBody()->getContents();
        static::assertJson($content);
    }

    /**
     * @test
     */
    public function checkJsonFromGetAllGlossariesIsValid(): void
    {
        $client = new Client();
        $createResponse = $client->createGlossary(
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

        $createResponse = json_decode($createResponse->getBody()->getContents(), true);
        $this->glossaryIdStorage[] = $createResponse['glossary_id'];

        $response = $client->getAllGlossaries();

        $content = $response->getBody()->getContents();
        $jsonObject = json_decode($content, true);

        static::assertJsonDocumentMatches($jsonObject, [
            '$.glossaries' => new IsType(IsType::TYPE_ARRAY),
            '$.glossaries[*].glossary_id' => new IsType(IsType::TYPE_STRING),
            '$.glossaries[*].ready' => new IsType(IsType::TYPE_BOOL),
            '$.glossaries[*].name' => new IsType(IsType::TYPE_STRING),
            '$.glossaries[*].source_lang' => new IsType(IsType::TYPE_STRING),
            '$.glossaries[*].target_lang' => new IsType(IsType::TYPE_STRING),
            '$.glossaries[*].entry_count' => new IsType(IsType::TYPE_INT),
        ]);

        static::assertJsonValueEquals($jsonObject, '$.glossaries[*].name', 'Deepl-Client-Create-Function-Test:' . __FUNCTION__);
        static::assertJsonValueEquals($jsonObject, '$.glossaries[*].source_lang', 'de');
        static::assertJsonValueEquals($jsonObject, '$.glossaries[*].target_lang', 'en');
    }

    /**
     * @test
     */
    public function checkResponseFromGetGlossary(): void
    {
        $client = new Client();
        $createResponse = $client->createGlossary(
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

        $createResponse = json_decode($createResponse->getBody()->getContents(), true);
        $this->glossaryIdStorage[] = $createResponse['glossary_id'];

        $response = $client->getGlossary($createResponse['glossary_id']);
        static::assertSame(200, $response->getStatusCode());
        $content = $response->getBody()->getContents();
        static::assertJson($content);
    }

    /**
     * @test
     */
    public function checkJsonFromGetGlossaryIsValid(): void
    {
        $client = new Client();
        $createResponse = $client->createGlossary(
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

        $createResponse = json_decode($createResponse->getBody()->getContents(), true);
        $this->glossaryIdStorage[] = $createResponse['glossary_id'];

        $response = $client->getGlossary($createResponse['glossary_id']);
        $content = $response->getBody()->getContents();
        $jsonObject = json_decode($content, true);

        static::assertJsonDocumentMatches($jsonObject, [
            '$.glossary_id' => new IsType(IsType::TYPE_STRING),
            '$.ready' => new IsType(IsType::TYPE_BOOL),
            '$.name' => new IsType(IsType::TYPE_STRING),
            '$.source_lang' => new IsType(IsType::TYPE_STRING),
            '$.target_lang' => new IsType(IsType::TYPE_STRING),
            '$.entry_count' => new IsType(IsType::TYPE_INT),
        ]);

        static::assertJsonValueEquals($jsonObject, '$.name', 'Deepl-Client-Create-Function-Test:' . __FUNCTION__);
        static::assertJsonValueEquals($jsonObject, '$.source_lang', 'de');
        static::assertJsonValueEquals($jsonObject, '$.target_lang', 'en');
    }

    /**
     * @test
     */
    public function checkResponseFromDeleteGlossary(): void
    {
        $client = new Client();
        $createResponse = $client->createGlossary(
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

        $createResponse = json_decode($createResponse->getBody()->getContents(), true);
        $this->glossaryIdStorage[] = $createResponse['glossary_id'];
        $response = $client->deleteGlossary($createResponse['glossary_id']);
        static::assertSame(204, $response->getStatusCode());

        $key = array_search($createResponse['glossary_id'], $this->glossaryIdStorage);
        unset($this->glossaryIdStorage[$key]);
    }

    /**
     * @test
     */
    public function checkResponseFromGetGlossaryEntries(): void
    {
        $client = new Client();
        $createResponse = $client->createGlossary(
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

        $createResponse = json_decode($createResponse->getBody()->getContents(), true);
        $this->glossaryIdStorage[] = $createResponse['glossary_id'];
        $response = $client->getGlossaryEntries($createResponse['glossary_id']);

        static::assertSame(200, $response->getStatusCode());
        $content = $response->getBody()->getContents();
        static::assertIsString($content);
    }

    /**
     * @test
     */
    public function checkTextFromGetGlossaryEntriesIsValid(): void
    {
        $client = new Client();
        $createResponse = $client->createGlossary(
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

        $createResponse = json_decode($createResponse->getBody()->getContents(), true);
        $this->glossaryIdStorage[] = $createResponse['glossary_id'];
        $response = $client->getGlossaryEntries($createResponse['glossary_id']);

        $content = $response->getBody()->getContents();

        static::assertSame(sprintf("%s\t%s", 'hallo Welt', 'hello world'), $content);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (!empty($this->glossaryIdStorage)) {
            $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
            $configuration = GeneralUtility::makeInstance(Configuration::class);

            foreach ($this->glossaryIdStorage as $glossaryId) {
                $baseUrl = sprintf(
                    '%s://%s/v2/glossaries/%s',
                    $configuration->getApiScheme(),
                    $configuration->getApiUrl(),
                    $glossaryId
                );

                $requestFactory->request($baseUrl, 'DELETE', [
                    'headers' => [
                        'Authorization' => sprintf('DeepL-Auth-Key %s', $configuration->getApiKey()),
                    ],
                ]);
            }
        }
    }
}
