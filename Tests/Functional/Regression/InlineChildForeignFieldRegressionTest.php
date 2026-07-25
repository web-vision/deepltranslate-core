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
 * Regression tests for https://github.com/web-vision/deepltranslate-core/issues/503
 *
 * The `deepltranslate` command is a plain `DataHandler::localize()` call. Localizing an inline
 * parent record works, because TYPO3 fills `DataHandler::$registerDBList` and finally repairs the
 * `foreign_field` pointer of the localized children through
 * `DataHandler::remapListedDBRecords_procInline()` -> `RelationHandler::writeForeignField()`.
 *
 * Dispatching the command for an inline *child* record - which is what
 * `web-vision/deepltranslate-auto-renew` does for a newly added child - never fills
 * `$registerDBList`, therefore the `foreign_field` pointer is never written and the translated
 * child stays orphaned.
 */
final class InlineChildForeignFieldRegressionTest extends AbstractDeepLTestCase
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
     * Control test / regression guard: dispatching the command on the inline **parent** record
     * localizes all children and attaches them to the translated parent. This is core behaviour
     * and expected to work.
     *
     * @test
     */
    public function translatingInlineParentAttachesLocalizedChildrenToTranslatedParent(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineRelationsParentLocalize.csv');

        $this->dispatchDeeplTranslateCommand('tx_testinlinerelations_parent', 1, 1);

        static::assertSame(2, $this->countRecords('tx_testinlinerelations_parent'));
        static::assertSame(4, $this->countRecords('tx_testinlinerelations_child_declared'));
        static::assertSame(4, $this->countRecords('tx_testinlinerelations_child_undeclared'));
        self::assertCSVDataSet(__DIR__ . '/Fixtures/Results/inlineRelationsParentLocalized.csv');
    }

    /**
     * Issue #503: dispatching the command for the inline **child** record itself must attach the
     * created translation to the already existing translated parent record.
     *
     * The `foreign_field` pointer column is configured as `type => passthrough` in TCA here, so
     * `DataHandler::copyRecord()` takes the value over unchanged - which leaves the translated
     * child pointing to the *default language* parent instead of the translated one.
     *
     * @test
     */
    public function translatingInlineChildWithTcaConfiguredPointerFieldAttachesTranslationToTranslatedParent(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineRelationsChildLocalize.csv');

        $this->dispatchDeeplTranslateCommand('tx_testinlinerelations_child_declared', 3, 1);

        static::assertSame(4, $this->countRecords('tx_testinlinerelations_child_declared'));
        self::assertCSVDataSet(__DIR__ . '/Fixtures/Results/inlineRelationsChildDeclaredLocalized.csv');
    }

    /**
     * Issue #503, same as above but with the `foreign_field` pointer column *not* configured in
     * TCA at all - the setup used in the issue report. `DataHandler::fillInFieldArray()` drops
     * values of columns unknown to the TCA schema, so the translated child ends up with an empty
     * pointer field.
     *
     * @test
     */
    public function translatingInlineChildWithoutTcaConfiguredPointerFieldAttachesTranslationToTranslatedParent(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineRelationsChildLocalize.csv');

        $this->dispatchDeeplTranslateCommand('tx_testinlinerelations_child_undeclared', 3, 1);

        static::assertSame(4, $this->countRecords('tx_testinlinerelations_child_undeclared'));
        self::assertCSVDataSet(__DIR__ . '/Fixtures/Results/inlineRelationsChildUndeclaredLocalized.csv');
    }

    /**
     * Issue #503: without a translated parent record a child translation cannot be attached to anything.
     * Nothing must be created in that case, mirroring `DataHandler::inlineLocalizeSynchronize()`.
     *
     * @test
     */
    public function translatingInlineChildWithoutTranslatedParentCreatesNoOrphanedTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineRelationsParentLocalize.csv');

        $this->dispatchDeeplTranslateCommand('tx_testinlinerelations_child_declared', 1, 1);

        static::assertSame(1, $this->countRecords('tx_testinlinerelations_parent'));
        static::assertSame(2, $this->countRecords('tx_testinlinerelations_child_declared'));
        self::assertCSVDataSet(__DIR__ . '/Fixtures/Results/inlineRelationsUntranslatedParentUnchanged.csv');
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
