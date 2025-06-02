<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Regression;

use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

final class LocalizationInlineRegressionTest extends AbstractDeepLTestCase
{
    use SiteBasedTestTrait;

    /**
     * @var non-empty-string[]
     */
    protected array $testExtensionsToLoad = [
        'web-vision/deepl-base',
        'web-vision/deepltranslate-core',
        __DIR__ . '/../Fixtures/Extensions/test_services_override',
    ];

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
                'deeplAllowedAutoTranslate' => false,
                'deeplAllowedReTranslate' => false,
            ],
        ],
        'DE' => [
            'id' => 1,
            'title' => 'Deutsch',
            'locale' => 'de_DE',
            'iso' => 'de',
            'hrefLang' => 'de-DE',
            'direction' => '',
            'custom' => [
                'deeplTargetLanguage' => 'DE',
                'deeplAllowedAutoTranslate' => true,
                'deeplAllowedReTranslate' => true,
            ],
        ],
    ];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'deepltranslate_core' => [
                'apiKey' => 'mock_server',
            ],
        ],
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/deepltranslate_core/Tests/Functional/Regression/Fixtures/Files' => 'fileadmin',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/localizationInline.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                additionalRootConfiguration: [
                    'deeplAllowedAutoTranslate' => true,
                    'deeplAllowedReTranslate' => true,
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'strict'),
            ],
        );
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)
            ->createFromUserPreferences($GLOBALS['BE_USER']);
    }

    /** @test */
    public function ensureInlineElementsTranslationOnLocalization(): void
    {
        $commandMap = [
            'pages' => [
                1 => [
                    'deepltranslate' => 1,
                ],
            ],
        ];
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $commandMap);
        $dataHandler->process_cmdmap();

        static::assertEmpty($dataHandler->errorLog);
        self::assertCSVDataSet(__DIR__ . '/Fixtures/Results/pageWithMediaResult.csv');
    }
}
