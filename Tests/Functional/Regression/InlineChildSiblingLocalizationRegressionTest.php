<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Regression;

use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

/**
 * Two inline children (uid 3 and 4) were added in the default language after their parent (uid 1) had
 * been translated (uid 2). Dispatching `deepltranslate` for one of them must only translate that child.
 */
final class InlineChildSiblingLocalizationRegressionTest extends AbstractDeepLTestCase
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineRelationsTwoNewChildrenLocalize.csv');
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
     * @test
     */
    public function deeplTranslateCommandOnOneOfTwoNewChildrenTranslatesOnlyThatChild(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [
            'tx_testinlinerelations_child_declared' => [
                3 => [
                    'deepltranslate' => 1,
                ],
            ],
        ]);
        $dataHandler->process_cmdmap();

        static::assertSame([], $dataHandler->errorLog);
        $translatedChild = $this->fetchTranslation('tx_testinlinerelations_child_declared', 3, 1);
        static::assertIsArray($translatedChild, 'Requested child has not been localized.');
        static::assertSame(2, (int)$translatedChild['parentid'], 'Requested child translation is not attached to the translated parent.');
        static::assertSame(self::EXAMPLE_TEXT['de'], $translatedChild['title'], 'Requested child has not been translated by DeepL.');
        static::assertSame(3, (int)$translatedChild['l10n_source'], 'Requested child translation has the wrong translation source.');
        static::assertFalse($this->fetchTranslation('tx_testinlinerelations_child_declared', 4, 1), 'Sibling child has been localized as well.');
        static::assertSame(
            [2, (int)$translatedChild['uid']],
            $this->fetchAttachedChildUids(2),
            'The translated parent does not hold its existing and the new child translation in this order.'
        );
        static::assertSame(2, (int)$this->fetchRecord('tx_testinlinerelations_parent', 2)['children_declared'], 'Relation counter of the translated parent is wrong.');
    }

    /**
     * @return int[] Uids of the children pointing to the given parent, in their sorting order
     */
    private function fetchAttachedChildUids(int $parentUid): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tx_testinlinerelations_child_declared');
        $queryBuilder->getRestrictions()->removeAll();

        return array_map(
            intval(...),
            $queryBuilder
                ->select('uid')
                ->from('tx_testinlinerelations_child_declared')
                ->where(
                    $queryBuilder->expr()->eq('parentid', $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                )
                ->orderBy('sorting')
                ->executeQuery()
                ->fetchFirstColumn()
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    private function fetchRecord(string $table, int $uid): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        return (array)$queryBuilder
            ->select('*')
            ->from($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();
    }

    /**
     * @return array<string, mixed>|false
     */
    private function fetchTranslation(string $table, int $defaultLanguageUid, int $languageId): array|false
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($defaultLanguageUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchAssociative();
    }
}
