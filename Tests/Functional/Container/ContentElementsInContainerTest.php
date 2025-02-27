<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Container;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;
use WebVision\Deepltranslate\Core\Tests\Functional\Fixtures\Traits\SiteBasedTestTrait;

final class ContentElementsInContainerTest extends AbstractDeepLTestCase
{
    use SiteBasedTestTrait;

    /**
     * @var non-empty-string[]
     */
    protected array $testExtensionsToLoad = [
        'b13/container',
        'web-vision/deepltranslate-core',
        __DIR__ . '/../Fixtures/Extensions/test_services_override',
        __DIR__ . '/Fixtures/Extensions/test_container',
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

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = array_merge(
            $this->configurationToUseInTestInstance,
            require __DIR__ . '/../Fixtures/ExtensionConfig.php'
        );

        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/page_with_container.csv');
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, '/', 'Home'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('EB', '/eb/', ['EN'], 'strict'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'strict'),
                $this->buildLanguageConfiguration('BS', '/bs/', ['EN'], 'strict'),
            ]
        );
        $this->setUpFrontendRootPage(1, [], []);

        $this->setUpBackendUser(1);

        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->createFromUserPreferences($GLOBALS['BE_USER']);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function containerAndInlineElementsAreTranslated(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $cmdMap = [
            'tt_content' => [
                2 => [
                    'deepltranslate' => 2,
                ],
            ],
        ];

        $dataHandler->start([], $cmdMap);
        $dataHandler->process_cmdmap();

        static::assertEmpty($dataHandler->errorLog);
        self::assertCSVDataSet(__DIR__ . '/Fixtures/Result/container_translated.csv');
    }
}
