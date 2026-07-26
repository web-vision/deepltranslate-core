<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Regression;

use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

/**
 * Regression guard for the TYPO3 Core defect forge #110281, found while working on issue #503.
 *
 * ``BackendUtility::getRecordLocalization()`` (and, on TYPO3 v14,
 * ``LocalizationRepository::getRecordTranslation()``) matches existing translations by the
 * ``translationSource`` field (``l10n_source``) when the table declares one, and does not fall back
 * to ``transOrigPointerField`` (``l10n_parent``). A valid translation with a correct ``l10n_parent``
 * but an empty ``l10n_source`` - the usual result of a plain DataHandler datamap, an importer, a
 * migration or MASK - is therefore invisible. ``DataHandler::localize()``, which the
 * ``deepltranslate`` command is, uses that lookup to detect existing translations, so it does not
 * see the translation and creates a **second** one in the same language.
 *
 * This is the root cause of the duplicated translations reported in
 * web-vision/deepltranslate-auto-renew#42. It cannot be fixed inside this extension; the fix is in
 * the TYPO3 Core (forge #110281, Gerrit 94914/94915/94916) and is applied for these test runs as a
 * ``require-dev`` Composer patch on ``typo3/cms-backend`` (see the ``patches/`` directory and
 * ``Documentation/CorePatches``). Without that patch this test fails.
 *
 * @see https://forge.typo3.org/issues/110281
 * @see https://github.com/web-vision/deepltranslate-auto-renew/issues/42
 */
final class EmptyL10nSourceCoreRegressionTest extends AbstractDeepLTestCase
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

    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = __DIR__ . '/../Fixtures/Extensions/test_inline_relations';

        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/l10nSourceEmpty.csv');
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
     * The default record (uid 1) already has a German translation (uid 2) whose l10n_source is
     * empty. Localizing it again to German must not create a duplicate - the existing translation
     * has to be recognised.
     */
    #[Test]
    public function localizingARecordWithAnEmptyL10nSourceTranslationCreatesNoDuplicate(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [
            'tx_testinlinerelations_l10nsource' => [
                1 => [
                    'deepltranslate' => 1,
                ],
            ],
        ]);
        $dataHandler->process_cmdmap();

        // Exactly the default record and its single German translation - not a second one. Without
        // the Core fix the existing translation is invisible to DataHandler::localize() and a
        // duplicate is created (three records). With the fix the existing translation is found and
        // reused, so the record count stays at two.
        $this->assertSame(2, $this->countRecords('tx_testinlinerelations_l10nsource'));
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
