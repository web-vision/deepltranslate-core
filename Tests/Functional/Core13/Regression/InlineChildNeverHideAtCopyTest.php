<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Core13\Regression;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

/**
 * The `deepltranslate` command localizes an inline child through its translated parent with a DataHandler of its
 * own. On TYPO3 v13 that DataHandler gets the "never hide at copy" setting of the calling one. A localized record of a
 * table with `hideAtCopy` is created hidden otherwise.
 *
 * Not run on TYPO3 v14: it has no such DataHandler setting and reads the user setting `neverHideAtCopy` of the
 * backend user, which the DataHandler gets from the calling one since DPL-264.
 */
#[Group('not-core-14')]
final class InlineChildNeverHideAtCopyTest extends AbstractDeepLTestCase
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
        $this->testExtensionsToLoad[] = __DIR__ . '/../../Fixtures/Extensions/test_inline_relations';
        $this->testExtensionsToLoad[] = __DIR__ . '/../../Fixtures/Extensions/test_hide_at_copy';

        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Regression/Fixtures/inlineRelationsChildLocalize.csv');
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

    #[Test]
    public function translatingInlineChildKeepsNeverHideAtCopyOfCallingDataHandler(): void
    {
        $dataHandler = $this->createDataHandler();
        $dataHandler->neverHideAtCopy = true;

        $dataHandler->process_cmdmap();

        $this->assertSame([], $dataHandler->errorLog);
        self::assertCSVDataSet(__DIR__ . '/../../Regression/Fixtures/Results/inlineRelationsChildDeclaredLocalized.csv');
        $this->assertSame(0, $this->fetchHidden('tx_testinlinerelations_child_declared', 4));
    }

    private function createDataHandler(): DataHandler
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [
            'tx_testinlinerelations_child_declared' => [
                3 => [
                    'deepltranslate' => 1,
                ],
            ],
        ]);
        return $dataHandler;
    }

    private function fetchHidden(string $table, int $uid): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder
            ->select('hidden')
            ->from($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();
    }
}
