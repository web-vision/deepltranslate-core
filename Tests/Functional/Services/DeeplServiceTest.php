<?php

declare(strict_types=1);

namespace WebVision\WvDeepltranslate\Tests\Functional\Services;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\WvDeepltranslate\Domain\Dto\TranslateOptions;
use WebVision\WvDeepltranslate\Service\DeeplService;

/**
 * @covers \WebVision\WvDeepltranslate\Service\DeeplService
 */
class DeeplServiceTest extends FunctionalTestCase
{
    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/wv_deepltranslate',
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = array_merge(
            $this->configurationToUseInTestInstance,
            require __DIR__ . '/../Fixtures/ExtensionConfig.php'
        );

        parent::setUp();

        $this->importDataSet(__DIR__ . '/../Fixtures/Settings.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/Language.xml');
    }

    /**
     * @test
     */
    public function translateContentFromDeToEn(): void
    {
        if (defined('DEEPL_MOCKSERVER_USED') && DEEPL_MOCKSERVER_USED === true) {
            static::markTestSkipped(__METHOD__ . ' skipped, because DEEPL MOCKSERVER do not support EN as TARGET language.');
        }
        $deeplService = GeneralUtility::makeInstance(DeeplService::class);

        $translateOptions = new TranslateOptions();
        $translateOptions->setSourceLanguage('DE');
        $translateOptions->setTargetLanguage('EN');

        $responseObject = $deeplService->translateRequest(
            'Ich möchte gerne übersetzt werden!',
            $translateOptions
        );

        static::assertSame('I would like to be translated!', $responseObject['translations'][0]['text']);
    }

    /**
     * @test
     */
    public function translateContentAndRespectHtmlTags(): void
    {
        if (defined('DEEPL_MOCKSERVER_USED') && DEEPL_MOCKSERVER_USED === true) {
            static::markTestSkipped(__METHOD__ . ' skipped, because DEEPL MOCKSERVER do not support the test with HTML Tags');
        }

        $deeplService = GeneralUtility::makeInstance(DeeplService::class);

        $translateOptions = new TranslateOptions();
        $translateOptions->setSourceLanguage('EN');
        $translateOptions->setTargetLanguage('DE');
        $translateOptions->setSplittingTags(['em', 'p', 'span']);

        $responseObject = $deeplService->translateRequest(
            '<p>Important species in blueberry (<span>include</span>) the Western flower thrips (<em>Frankliniella occidentalis</em>) and Chilli thrips (<em>Scirtothrips dorsalis</em>).</p>',
            $translateOptions
        );

        static::assertSame(
            '<p>Wichtige Arten in der Heidelbeere (<span>gehören</span>) der Westliche Blütenthrips (<em>Frankliniella occidentalis</em>) und Chili-Thripse (<em>Scirtothrips dorsalis</em>).</p>',
            $responseObject['translations'][0]['text']
        );
    }

    /**
     * @test
     */
    public function translateContentFromEnToDe(): void
    {
        $translateContent = 'I would like to be translated!';
        $expectedTranslation = 'Ich möchte gerne übersetzt werden!';
        if (defined('DEEPL_MOCKSERVER_USED') && DEEPL_MOCKSERVER_USED === true) {
            $translateContent = 'proton beam';
            $expectedTranslation = 'Protonenstrahl';
        }
        $deeplService = GeneralUtility::makeInstance(DeeplService::class);

        $translateOptions = new TranslateOptions();
        $translateOptions->setSourceLanguage('EN');
        $translateOptions->setTargetLanguage('DE');

        $responseObject = $deeplService->translateRequest(
            $translateContent,
            $translateOptions
        );

        static::assertSame($expectedTranslation, $responseObject['translations'][0]['text']);
    }

    /**
     * @test
     */
    public function translateContentWithAutoDetectSourceParam(): void
    {
        $translateContent = 'I would like to be translated!';
        $expectedTranslation = 'Ich möchte gerne übersetzt werden!';
        if (defined('DEEPL_MOCKSERVER_USED') && DEEPL_MOCKSERVER_USED === true) {
            $translateContent = 'proton beam';
            $expectedTranslation = 'Protonenstrahl';
        }
        $deeplService = GeneralUtility::makeInstance(DeeplService::class);

        $translateOptions = new TranslateOptions();
        $translateOptions->setSourceLanguage('auto');
        $translateOptions->setTargetLanguage('DE');

        $responseObject = $deeplService->translateRequest(
            $translateContent,
            $translateOptions
        );

        static::assertSame($expectedTranslation, $responseObject['translations'][0]['text']);
    }

    /**
     * @test
     */
    public function checkSupportedTargetLanguages(): void
    {
        $deeplService = GeneralUtility::makeInstance(DeeplService::class);

        static::assertContains('EN-GB', $deeplService->apiSupportedLanguages['target']);
        static::assertContains('EN-US', $deeplService->apiSupportedLanguages['target']);
        static::assertContains('DE', $deeplService->apiSupportedLanguages['target']);
        static::assertContains('UK', $deeplService->apiSupportedLanguages['target']);
        static::assertNotContains('EN', $deeplService->apiSupportedLanguages['target']);
        static::assertNotContains('BS', $deeplService->apiSupportedLanguages['target']);
    }

    /**
     * @test
     */
    public function checkFormalitySupportedLanguages(): void
    {
        $deeplService = GeneralUtility::makeInstance(DeeplService::class);

        static::assertContains('ES', $deeplService->formalitySupportedLanguages);
        static::assertContains('DE', $deeplService->formalitySupportedLanguages);
        static::assertContains('NL', $deeplService->formalitySupportedLanguages);
        static::assertNotContains('EN', $deeplService->formalitySupportedLanguages);
        static::assertNotContains('BS', $deeplService->formalitySupportedLanguages);
    }

    /**
     * @test
     */
    public function checkSupportedSourceLanguages(): void
    {
        $deeplService = GeneralUtility::makeInstance(DeeplService::class);

        static::assertContains('DE', $deeplService->apiSupportedLanguages['source']);
        static::assertContains('UK', $deeplService->apiSupportedLanguages['source']);
        static::assertContains('EN', $deeplService->apiSupportedLanguages['source']);
        static::assertNotContains('EN-GB', $deeplService->apiSupportedLanguages['source']);
        static::assertNotContains('EN-US', $deeplService->apiSupportedLanguages['source']);
        static::assertNotContains('BS', $deeplService->apiSupportedLanguages['source']);
    }
}
