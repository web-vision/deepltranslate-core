<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Hooks;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Hooks\TranslateHook;
use WebVision\Deepltranslate\Core\Service\LanguageService;
use WebVision\Deepltranslate\Core\Service\ProcessingInstruction;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

#[CoversClass(TranslateHook::class)]
final class TranslateHookTest extends AbstractDeepLTestCase
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

        /** @var ProcessingInstruction $processingInstruction */
        $processingInstruction = $this->get(ProcessingInstruction::class);
        $processingInstruction->setProcessingInstruction(null, null, true);
    }

    #[Test]
    public function contentTranslateWithDeepl(): void
    {
        $translateContent = 'proton beam';
        $expectedTranslation = 'Protonenstrahl';

        /** @var TranslateHook $translateHook */
        $translateHook = $this->get(TranslateHook::class);
        $languageService = $this->get(LanguageService::class);

        /** @var SiteFinder $siteFinder */
        $siteFinder = $this->get(SiteFinder::class);
        $siteConfig = $siteFinder->getSiteByPageId(1);

        $sourceLanguageRecord = $languageService->getSourceLanguage($siteConfig);
        $content = $translateHook->translateContent(
            $translateContent,
            $sourceLanguageRecord['language_isocode'],
            'DE',
        );

        static::assertSame($expectedTranslation, $content);
    }

    #[Test]
    public function contentNotTranslateWithDeeplWhenLanguageNotSupported(): void
    {
        $serverParams = array_replace($_SERVER, ['HTTP_HOST' => 'example.com', 'SCRIPT_NAME' => '/typo3/index.php']);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('http://example.com/typo3/index.php', 'GET', null, $serverParams))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', NormalizedParams::createFromServerParams($serverParams));

        /** @var TranslateHook $translateHook */
        $translateHook = $this->get(TranslateHook::class);
        $languageService = $this->get(LanguageService::class);
        /** @var SiteFinder $siteFinder */
        $siteFinder = $this->get(SiteFinder::class);
        $siteConfig = $siteFinder->getSiteByPageId(1);

        $sourceLanguageRecord = $languageService->getSourceLanguage($siteConfig);
        $content = $translateHook->translateContent(
            'Hello I would like to be translated',
            $sourceLanguageRecord['language_isocode'],
            'BS'
        );

        static::assertSame('', $content);
    }

    #[Test]
    public function translateContentElementsAndUpdatePagesProperties(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BeUsersTranslateDeeplFlag.csv');
        $this->setUpBackendUser(2);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->createFromUserPreferences($GLOBALS['BE_USER']);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $cmdMap = [
            'tt_content' => [
                1 => [
                    'deepltranslate' => 2,
                ],
            ],
        ];

        $dataHandler->start([], $cmdMap);
        $dataHandler->process_cmdmap();

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages');
        $pageRow = $connection->select(
            [
                'uid',
                'tx_wvdeepltranslate_content_not_checked',
                'tx_wvdeepltranslate_translated_time',
            ],
            'pages',
            [
                'uid' => 2,
            ],
            [],
            [],
            1,
        )->fetchAssociative();

        static::assertIsArray($pageRow);
        static::assertArrayHasKey('tx_wvdeepltranslate_content_not_checked', $pageRow);
        static::assertSame(1, (int)$pageRow['tx_wvdeepltranslate_content_not_checked']);
        static::assertArrayHasKey('tx_wvdeepltranslate_translated_time', $pageRow);
        static::assertGreaterThan(0, (int)$pageRow['tx_wvdeepltranslate_translated_time']);
    }
}
