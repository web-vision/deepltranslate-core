<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Regression;

use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

final class PreviewTranslationInformationTest extends AbstractDeepLTestCase
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
            'wv_deepltranslate' => [
                'apiKey' => 'mock_server',
            ],
        ],
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/deepltranslate_core/Tests/Functional/Regression/Fixtures/Files' => 'fileadmin',
    ];

    protected function setUp(): void
    {
        $this->coreExtensionsToLoad[] = 'typo3/cms-fluid-styled-content';
        $this->testExtensionsToLoad[] = __DIR__ . '/../Fixtures/Extensions/testing_framework_backenduserhandler_replacement';
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PreviewTranslationInformation.csv');
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: 'https://acme.com/',
                additionalRootConfiguration: [
                    'deeplAllowedAutoTranslate' => true,
                    'deeplAllowedReTranslate' => true,
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration('EN', 'https://acme.com/'),
                $this->buildLanguageConfiguration('DE', 'https://acme.com/de/', ['EN'], 'strict'),
            ],
        );
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:fluid_styled_content/Configuration/TypoScript/Styling/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:fluid_styled_content/Configuration/TypoScript/Styling/setup.typoscript',
                    'EXT:deepltranslate_core/Tests/Functional/Regression/Fixtures/PreviewTranslationInformation.typoscript',
                ],
            ],
            templateValues: [
                'title' => 'ACME Root',
            ],
        );
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)
            ->createFromUserPreferences($GLOBALS['BE_USER']);
    }

    /**
     * @test
     */
    public function previewTranslationInformationIsRenderedForTranslatedPage(): void
    {
        $styles = [];
        $styles[] = 'position: fixed';
        $styles[] = 'top: 65px';
        $styles[] = 'right: 15px';
        $styles[] = 'padding: 8px 18px';
        $styles[] = 'background: #006494';
        $styles[] = 'border: 1px solid #006494';
        $styles[] = 'font-family: sans-serif';
        $styles[] = 'font-size: 14px';
        $styles[] = 'font-weight: bold';
        $styles[] = 'color: #fff';
        $styles[] = 'z-index: 20000';
        $styles[] = 'user-select: none';
        $styles[] = 'pointer-events: none';
        $styles[] = 'text-align: center';
        $styles[] = 'border-radius: 2px';
        $expectedContent = '<div id="deepl-preview-info" style="' . implode(';', $styles) . '">' . htmlspecialchars('Translated with DeepL') . '</div>';

        $requestContext = (new InternalRequestContext())->withBackendUserId(1);
        $request = new InternalRequest('https://acme.com/de/artikel/');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        static::assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        static::assertNotEmpty($content);
        static::assertStringContainsString($expectedContent, $content, 'preview translation label is rendered in frontend preview');
    }
}
