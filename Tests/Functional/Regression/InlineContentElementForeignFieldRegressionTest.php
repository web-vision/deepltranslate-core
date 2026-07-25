<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Regression;

use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

/**
 * Real-world regression tests for https://github.com/web-vision/deepltranslate-core/issues/503 using
 * the shared `tt_content` table as the inline child, the way EXT:news embeds content elements into
 * `tx_news_domain_model_news.content_elements`.
 *
 * The point of these tests is the reliable distinction, raised in review, between the two roles a
 * `tt_content` record can have:
 *
 * - a genuine inline child of another record - it must be localized through its parent so the
 *   `foreign_field` pointer (`tx_testinlinerelations_related`) is written to the translated parent;
 * - a normal content element placed directly on a page - it must keep being localized on its own,
 *   exactly as before, and must not be routed through a non-existing parent.
 */
final class InlineContentElementForeignFieldRegressionTest extends AbstractDeepLTestCase
{
    use SiteBasedTestTrait;

    /**
     * @var non-empty-string[]
     */
    protected array $testExtensionsToLoad = [
        'web-vision/deepl-base',
        'web-vision/deeplcom-deepl-php',
        'web-vision/deepltranslate-core',
        __DIR__ . '/../Fixtures/Extensions/test_services_override',
        __DIR__ . '/../Fixtures/Extensions/test_inline_relations',
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineContentElementLocalize.csv');
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

    /**
     * Issue #503 for the real-world `tt_content` case: a content element used as an inline child of
     * another record (`tx_testinlinerelations_related` set) is localized through its parent, so the
     * pointer of the created translation targets the *translated* parent record.
     *
     * @test
     */
    public function translatingContentElementUsedAsInlineChildAttachesTranslationToTranslatedParent(): void
    {
        $this->dispatchDeeplTranslateCommand('tt_content', 10, 1);

        static::assertSame(3, $this->countRecords('tt_content'));
        self::assertCSVDataSet(__DIR__ . '/Fixtures/Results/inlineContentElementChildLocalized.csv');
    }

    /**
     * The counterpart: a `tt_content` element placed directly on a page (no inline parent) must keep
     * being localized normally - a translation of the element itself, with the pointer column left
     * untouched. It must not be treated as an inline child.
     *
     * @test
     */
    public function translatingContentElementOnPageLocalizesItNormally(): void
    {
        $this->dispatchDeeplTranslateCommand('tt_content', 20, 1);

        static::assertSame(3, $this->countRecords('tt_content'));
        self::assertCSVDataSet(__DIR__ . '/Fixtures/Results/inlineContentElementPageCeLocalized.csv');
    }

    private function dispatchDeeplTranslateCommand(string $table, int $uid, int $targetLanguageId): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [
            $table => [
                $uid => [
                    'deepltranslate' => $targetLanguageId,
                ],
            ],
        ]);
        $dataHandler->process_cmdmap();

        static::assertSame([], $dataHandler->errorLog);
    }

    private function countRecords(string $table): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder
            ->count('uid')
            ->from($table)
            ->executeQuery()
            ->fetchOne();
    }
}
