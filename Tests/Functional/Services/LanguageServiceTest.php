<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Services;

use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Site\SiteFinder;
use WebVision\Deepltranslate\Core\Exception\LanguageIsoCodeNotFoundException;
use WebVision\Deepltranslate\Core\Exception\LanguageRecordNotFoundException;
use WebVision\Deepltranslate\Core\Service\LanguageService;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

final class LanguageServiceTest extends AbstractDeepLTestCase
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
        'BS_default' => [
            'id' => 0,
            'title' => 'Bosnian',
            'locale' => 'bs_BA.utf8',
            'iso' => 'bs',
            'hrefLang' => 'bs',
            'direction' => '',
            'custom' => [
                'deeplTargetLanguage' => '',
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

        $this->importCSVDataSet(__DIR__ . '/Fixtures/Pages.csv');
        $this->writeSiteConfiguration(
            identifier: 'site-a',
            site: $this->buildSiteConfiguration(rootPageId: 1),
            languages: [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'strict'),
                $this->buildLanguageConfiguration('EB', '/eb/', ['EN'], 'strict'),
                $this->buildLanguageConfiguration('BS', '/bs/', ['EN'], 'strict'),
            ],
        );
        $this->setUpFrontendRootPage(1, [], []);
        $this->writeSiteConfiguration(
            identifier: 'site-b',
            site: $this->buildSiteConfiguration(rootPageId: 3),
            languages: [
                $this->buildDefaultLanguageConfiguration('BS_default', '/bs/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'strict'),
                $this->buildLanguageConfiguration('EB', '/eb/', ['EN'], 'strict'),
            ],
        );
        $this->setUpFrontendRootPage(pageId: 3);
    }

    #[Test]
    public function getSourceLanguageInformationIsValid(): void
    {
        /** @var LanguageService $languageService */
        $languageService = $this->get(LanguageService::class);
        /** @var SiteFinder $siteFinder */
        $siteFinder = $this->get(SiteFinder::class);
        $siteInformation = $siteFinder->getSiteByPageId(1);

        $sourceLanguageRecord = $languageService->getSourceLanguage($siteInformation);

        static::assertArrayHasKey('uid', $sourceLanguageRecord);
        static::assertArrayHasKey('title', $sourceLanguageRecord);
        static::assertArrayHasKey('language_isocode', $sourceLanguageRecord);

        static::assertSame(0, $sourceLanguageRecord['uid']);
        static::assertSame('EN', $sourceLanguageRecord['language_isocode']);
    }

    #[Test]
    public function setAutoDetectOptionForSourceLanguageNotSupported(): void
    {
        /** @var LanguageService $languageService */
        $languageService = $this->get(LanguageService::class);
        /** @var SiteFinder $siteFinder */
        $siteFinder = $this->get(SiteFinder::class);
        $siteInformation = $siteFinder->getSiteByPageId(3);

        $sourceLanguageRecord = $languageService->getSourceLanguage($siteInformation);

        static::assertContains('auto', $sourceLanguageRecord);
    }

    #[Test]
    public function getTargetLanguageInformationIsValid(): void
    {
        /** @var LanguageService $languageService */
        $languageService = $this->get(LanguageService::class);
        /** @var SiteFinder $siteFinder */
        $siteFinder = $this->get(SiteFinder::class);
        $siteInformation = $siteFinder->getSiteByPageId(1);

        $sourceLanguageRecord = $languageService->getTargetLanguage($siteInformation, 2);

        static::assertArrayHasKey('uid', $sourceLanguageRecord);
        static::assertArrayHasKey('title', $sourceLanguageRecord);
        static::assertArrayHasKey('language_isocode', $sourceLanguageRecord);

        static::assertSame(2, $sourceLanguageRecord['uid']);
        static::assertSame('DE', $sourceLanguageRecord['language_isocode']);
    }

    #[Test]
    public function getTargetLanguageExceptionWhenLanguageNotExist(): void
    {
        /** @var LanguageService $languageService */
        $languageService = $this->get(LanguageService::class);
        /** @var SiteFinder $siteFinder */
        $siteFinder = $this->get(SiteFinder::class);
        $siteInformation = $siteFinder->getSiteByPageId(1);

        static::expectException(LanguageRecordNotFoundException::class);
        static::expectExceptionMessage('Language "1" not found in SiteConfig "Home"');
        $languageService->getTargetLanguage($siteInformation, 1);
    }

    #[Test]
    public function getTargetLanguageExceptionWhenLanguageIsoNotSupported(): void
    {
        /** @var LanguageService $languageService */
        $languageService = $this->get(LanguageService::class);
        /** @var SiteFinder $siteFinder */
        $siteFinder = $this->get(SiteFinder::class);
        $siteInformation = $siteFinder->getSiteByPageId(1);

        static::expectException(LanguageIsoCodeNotFoundException::class);
        static::expectExceptionMessage('No API supported target found for language "Bosnian" in site "Home"');
        $languageService->getTargetLanguage($siteInformation, 4);
    }
}
