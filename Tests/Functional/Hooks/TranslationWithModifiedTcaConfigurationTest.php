<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Hooks;

use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

final class TranslationWithModifiedTcaConfigurationTest extends AbstractDeepLTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => [
            'id' => 0,
            'title' => 'English',
            'locale' => 'en_US.UTF-8',
            'iso' => 'en',
            'hrefLang' => 'en-US',
            'direction' => '',
            'custom' => [
                'deeplTargetLanguage' => '',
            ],
        ],
        'DE' => [
            'id' => 2,
            'title' => 'Deutsch',
            'locale' => 'de_DE',
            'iso' => 'de',
            'hrefLang' => 'de-DE',
            'direction' => '',
            'custom' => [
                'deeplTargetLanguage' => 'DE',
            ],
        ],
        'EB' => [
            'id' => 3,
            'title' => 'Britisch',
            'locale' => 'en_GB',
            'iso' => 'eb',
            'hrefLang' => 'en-GB',
            'direction' => '',
            'custom' => [
                'deeplTargetLanguage' => 'EN-GB',
            ],
        ],
        'BS' => [
            'id' => 4,
            'title' => 'Bosnian',
            'locale' => 'bs_BA.utf8',
            'iso' => 'bs',
            'hrefLang' => 'bs',
            'direction' => '',
            'custom' => [
                'deeplTargetLanguage' => '',
            ],
        ],
    ];

    /**
     * @var non-empty-string[]
     */
    protected array $testExtensionsToLoad = [
        'web-vision/deepl-base',
        'web-vision/deeplcom-deepl-php',
        'web-vision/deepltranslate-core',
        __DIR__ . '/../Fixtures/Extensions/test_services_override',
        __DIR__ . '/Fixtures/Extensions/test_tca_override',
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = array_merge(
            $this->configurationToUseInTestInstance,
            require __DIR__ . '/../Fixtures/ExtensionConfig.php'
        );

        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('EB', '/eb/', ['EN'], 'strict'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'strict'),
                $this->buildLanguageConfiguration('BS', '/bs/', ['EN'], 'strict'),
            ],
        );
        $this->setUpFrontendRootPage(
            pageId: 1,
        );
    }

    #[Test]
    public function pageIsBeingTranslated(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->createFromUserPreferences($GLOBALS['BE_USER']);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $cmdMap = [
            'pages' => [
                3 => [
                    'deepltranslate' => 2,
                ],
            ],
        ];

        $dataHandler->start([], $cmdMap);
        $dataHandler->process_cmdmap();

        $this->assertEmpty($dataHandler->errorLog);
        self::assertCSVDataSet(__DIR__ . '/Fixtures/Results/page_translated.csv');
    }
}
