<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Regression;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\Model\CorrelationId;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;
use WebVision\Deepltranslate\Core\Tests\Functional\Regression\Fixtures\Classes\RecordImportingStateHook;

/**
 * The `deepltranslate` command localizes an inline child through its translated parent with a DataHandler of its
 * own. That DataHandler gets the settings of the calling one that TYPO3 hands to the DataHandlers it starts itself.
 */
final class InlineChildDataHandlerSettingsRegressionTest extends AbstractDeepLTestCase
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineRelationsChildLocalize.csv');
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
        RecordImportingStateHook::$seen = [];
    }

    protected function tearDown(): void
    {
        RecordImportingStateHook::$seen = [];
        parent::tearDown();
    }

    /**
     * An import that localizes an inline child, a seed for example, stays an import for the DataHandler hooks of the
     * DataHandlers started for it. Hooks that skip imports rely on it.
     */
    #[Test]
    public function translatingInlineChildKeepsImportOfCallingDataHandler(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][]
            = RecordImportingStateHook::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
            = RecordImportingStateHook::class;
        $dataHandler = $this->createDataHandler();
        $dataHandler->isImporting = true;

        $dataHandler->process_cmdmap();

        $this->assertSame([], $dataHandler->errorLog);
        self::assertCSVDataSet(__DIR__ . '/Fixtures/Results/inlineRelationsChildDeclaredLocalized.csv');
        $this->assertSame(
            [
                ['deepltranslate tx_testinlinerelations_child_declared:3', true],
                ['inlineLocalizeSynchronize tx_testinlinerelations_parent:1', true],
                ['datamap tx_testinlinerelations_child_declared', true],
            ],
            RecordImportingStateHook::$seen
        );
    }

    #[Test]
    public function translatingInlineChildKeepsDisabledLoggingOfCallingDataHandler(): void
    {
        $dataHandler = $this->createDataHandler();
        $dataHandler->enableLogging = false;

        $dataHandler->process_cmdmap();

        $this->assertSame([], $dataHandler->errorLog);
        self::assertCSVDataSet(__DIR__ . '/Fixtures/Results/inlineRelationsChildDeclaredLocalized.csv');
        $this->assertSame(0, $this->countRecords('sys_log'));
    }

    /**
     * The editor has access to the tables and languages, but not to the page of the records. The calling DataHandler
     * bypasses the record access check, the DataHandler localizing the child has to bypass it as well.
     */
    #[Test]
    public function translatingInlineChildKeepsBypassedRecordAccessCheckOfCallingDataHandler(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/beUserEditorWithoutPageAccess.csv');
        $this->setUpBackendUser(2);
        $dataHandler = $this->createDataHandler();
        $dataHandler->bypassAccessCheckForRecords = true;

        $dataHandler->process_cmdmap();

        $this->assertSame([], $dataHandler->errorLog);
        self::assertCSVDataSet(__DIR__ . '/Fixtures/Results/inlineRelationsChildDeclaredLocalized.csv');
    }

    /**
     * The history record of the translation is written by the DataHandler TYPO3 starts to copy the record. TYPO3 v13
     * gives that one a correlation id of its own, only TYPO3 v14 hands it on.
     */
    #[Test]
    #[Group('not-core-13')]
    public function translatingInlineChildKeepsCorrelationIdOfCallingDataHandler(): void
    {
        $dataHandler = $this->createDataHandler();
        $scope = $dataHandler->getCorrelationId()?->getScope();
        $this->assertNotNull($scope);

        $dataHandler->process_cmdmap();

        $this->assertSame([], $dataHandler->errorLog);
        self::assertCSVDataSet(__DIR__ . '/Fixtures/Results/inlineRelationsChildDeclaredLocalized.csv');
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_history');
        $queryBuilder->getRestrictions()->removeAll();
        $correlationIds = $queryBuilder
            ->select('correlation_id')
            ->from('sys_history')
            ->where(
                $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter('tx_testinlinerelations_child_declared')),
                $queryBuilder->expr()->eq('recuid', $queryBuilder->createNamedParameter(4, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchFirstColumn();
        $this->assertCount(1, $correlationIds);
        $this->assertSame($scope, CorrelationId::fromString((string)$correlationIds[0])->getScope());
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
