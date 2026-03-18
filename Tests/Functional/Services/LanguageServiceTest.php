<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Services;

use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Site\SiteFinder;
use WebVision\Deepltranslate\Core\Exception\InvalidArgumentException;
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
                'deeplTargetLanguage' => 'BS',
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
        'Not-supported' => [
            'id' => 5,
            'title' => 'Bosnian',
            'locale' => 'bs_BA.utf8',
            'iso' => 'bs',
            'hrefLang' => 'bs',
            'direction' => '',
            'custom' => [
                'deeplTargetLanguage' => 'BS',
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
                $this->buildLanguageConfiguration('Not-supported', '/not-supported/', ['EN'], 'strict'),
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

        $this->assertArrayHasKey('uid', $sourceLanguageRecord);
        $this->assertArrayHasKey('title', $sourceLanguageRecord);
        $this->assertArrayHasKey('language_isocode', $sourceLanguageRecord);

        $this->assertSame(0, $sourceLanguageRecord['uid']);
        $this->assertSame('EN', $sourceLanguageRecord['language_isocode']);
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

        $this->assertContains('auto', $sourceLanguageRecord);
    }

    #[Test]
    public function getTargetLanguageInformationIsValid(): void
    {
        /** @var LanguageService $languageService */
        $languageService = $this->get(LanguageService::class);
        /** @var SiteFinder $siteFinder */
        $siteFinder = $this->get(SiteFinder::class);
        $siteInformation = $siteFinder->getSiteByPageId(1);

        $targetLanguageRecord = $languageService->getTargetLanguage($siteInformation, 2);
        $this->assertIsArray($targetLanguageRecord);

        $this->assertArrayHasKey('uid', $targetLanguageRecord);
        $this->assertArrayHasKey('title', $targetLanguageRecord);
        $this->assertArrayHasKey('language_isocode', $targetLanguageRecord);

        $this->assertSame(2, $targetLanguageRecord['uid']);
        $this->assertSame('DE', $targetLanguageRecord['language_isocode']);
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
        static::expectExceptionCode(1746959505);
        static::expectExceptionMessage(sprintf('Language "%s" in site "%s" not found.', 1, 'site-a'));
        $languageService->getTargetLanguage($siteInformation, 1);
    }

    #[Test]
    public function getTargetLanguageReturnsFalseOnNotConfiguredTargetLanguage(): void
    {
        /** @var LanguageService $languageService */
        $languageService = $this->get(LanguageService::class);
        /** @var SiteFinder $siteFinder */
        $siteFinder = $this->get(SiteFinder::class);
        $siteInformation = $siteFinder->getSiteByPageId(1);

        static::expectException(InvalidArgumentException::class);
        static::expectExceptionCode(1746973481);
        static::expectExceptionMessage(sprintf('Missing deeplTargetLanguage or Language "%s" in site "%s"', 4, 'site-a'));
        $languageService->getTargetLanguage($siteInformation, 4);
    }

    #[Test]
    public function getTargetLanguageExceptionWhenLanguageIsoNotSupported(): void
    {
        /** @var LanguageService $languageService */
        $languageService = $this->get(LanguageService::class);
        /** @var SiteFinder $siteFinder */
        $siteFinder = $this->get(SiteFinder::class);
        $siteInformation = $siteFinder->getSiteByPageId(1);

        static::expectException(InvalidArgumentException::class);
        static::expectExceptionCode(1746959745);
        static::expectExceptionMessage(sprintf('The given language key "%s" is not supported by DeepL. Possibly wrong Site configuration.', 'BS'));
        $languageService->getTargetLanguage($siteInformation, 5);
    }
}
