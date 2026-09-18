<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Regression;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Service\ProcessingInstruction;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;
use WebVision\Deepltranslate\Core\Tests\Functional\Form\InlineLocalizeWithDeeplControlTest;

/**
 * Reproduction for https://github.com/web-vision/deepltranslate-core/issues/558 (DPL-232).
 *
 * Documents how the core inline buttons behave on a translated parent, and guards the DeepL
 * translation of inline children through their translated parent (DPL-193).
 *
 * `tx_testinlinerelations_parent` stands in for the bootstrap_package card group content element,
 * `tx_testinlinerelations_child_declared` for its inline card items.
 */
final class InlineChildLocalizationIssue558Test extends AbstractDeepLTestCase
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

    public static function inlineButtonCommandDataProvider(): \Generator
    {
        // FormInlineAjaxController::synchronizeLocalizeAction(): the command is always addressed to the
        // parent record currently edited, i.e. the *translated* parent, with the parent's language.
        yield 'single child "localize" button (ids)' => [
            'inlineCommand' => [
                'field' => 'children_declared',
                'language' => 1,
                'ids' => [3],
            ],
        ];
        yield '"Localize all records" button (action localize)' => [
            'inlineCommand' => [
                'field' => 'children_declared',
                'language' => 1,
                'action' => 'localize',
            ],
        ];
        yield '"Synchronize with default language" button (action synchronize)' => [
            'inlineCommand' => [
                'field' => 'children_declared',
                'language' => 1,
                'action' => 'synchronize',
            ],
        ];
    }

    /**
     * Flow 1: parent (uid 1) and its first child (uid 1) are already DeepL translated (parent uid 2,
     * child uid 2). A second child (uid 3) was added later in the default language and is now localized
     * with the inline buttons of the translated parent.
     *
     * These buttons are core actions and deliberately keep their core behaviour: the child is localized
     * and attached to the translated parent, but its content is copied, not translated with DeepL. The
     * DeepL entry point for this case is the "Localize with DeepL" control of the inline child, see
     * {@see InlineLocalizeWithDeeplControlTest}.
     *
     * @param array<string, mixed> $inlineCommand
     */
    #[Test]
    #[DataProvider('inlineButtonCommandDataProvider')]
    public function coreInlineButtonsOnTranslatedParentLocalizeNewChildByCopyWithoutDeepl(array $inlineCommand): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineRelationsChildLocalize.csv');

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [
            'tx_testinlinerelations_parent' => [
                2 => [
                    'inlineLocalizeSynchronize' => $inlineCommand,
                ],
            ],
        ]);
        $dataHandler->process_cmdmap();
        $this->assertSame([], $dataHandler->errorLog);

        $translatedChild = $this->fetchTranslation('tx_testinlinerelations_child_declared', 3, 1);
        $this->assertIsArray($translatedChild, 'New child has not been localized at all.');
        $this->assertSame(2, (int)$translatedChild['parentid'], 'New child translation is not attached to the translated parent.');
        $this->assertSame(self::EXAMPLE_TEXT['en'], $translatedChild['title'], 'Core inline buttons are expected to copy the content.');
    }

    /**
     * Flow 1, root cause probe: the very same core command translates with DeepL as soon as the
     * extension's DeepL mode flag is switched on for the request, which only the `deepltranslate`
     * command does.
     *
     * @param array<string, mixed> $inlineCommand
     */
    #[Test]
    #[DataProvider('inlineButtonCommandDataProvider')]
    public function inlineButtonsTranslateWithDeeplOnceDeeplModeIsSetForTheRequest(array $inlineCommand): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineRelationsChildLocalize.csv');
        $this->get(ProcessingInstruction::class)->setProcessingInstruction('tx_testinlinerelations_parent', 2, true);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [
            'tx_testinlinerelations_parent' => [
                2 => [
                    'inlineLocalizeSynchronize' => $inlineCommand,
                ],
            ],
        ]);
        $dataHandler->process_cmdmap();
        $this->assertSame([], $dataHandler->errorLog);

        $translatedChild = $this->fetchTranslation('tx_testinlinerelations_child_declared', 3, 1);
        $this->assertIsArray($translatedChild, 'New child has not been localized at all.');
        $this->assertSame(2, (int)$translatedChild['parentid'], 'New child translation is not attached to the translated parent.');
        $this->assertSame(self::EXAMPLE_TEXT['de'], $translatedChild['title'], 'New child translation has not been translated by DeepL.');
    }

    /**
     * Flow 1, for comparison: the same new child localized with the extension's `deepltranslate`
     * command (list module DeepL button) instead of the core inline buttons.
     */
    #[Test]
    public function deeplTranslateCommandOnNewChildLocalizesItWithDeeplAndAttachesItToTranslatedParent(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineRelationsChildLocalize.csv');

        $this->dispatchDeeplTranslateCommand([
            'tx_testinlinerelations_child_declared' => [
                3 => [
                    'deepltranslate' => 1,
                ],
            ],
        ]);

        $translatedChild = $this->fetchTranslation('tx_testinlinerelations_child_declared', 3, 1);
        $this->assertIsArray($translatedChild, 'New child has not been localized at all.');
        $this->assertSame(2, (int)$translatedChild['parentid'], 'New child translation is not attached to the translated parent.');
        $this->assertSame(self::EXAMPLE_TEXT['de'], $translatedChild['title'], 'New child translation has not been translated by DeepL.');
    }

    /**
     * Flow 2: in the list module the children are DeepL translated one by one *before* their parent.
     * Nothing may be created for them (DPL-193). Translating the parent afterwards must translate all
     * children with DeepL and attach them to the translated parent.
     */
    #[Test]
    public function translatingChildrenBeforeParentInListModuleCreatesNoMisattachedTranslations(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineRelationsParentLocalize.csv');

        foreach ([1, 2] as $childUid) {
            $this->dispatchDeeplTranslateCommand([
                'tx_testinlinerelations_child_declared' => [
                    $childUid => [
                        'deepltranslate' => 1,
                    ],
                ],
            ]);
        }
        $this->assertSame(2, $this->countRecords('tx_testinlinerelations_child_declared'), 'Children translated before their parent must be skipped.');

        $this->dispatchDeeplTranslateCommand([
            'tx_testinlinerelations_parent' => [
                1 => [
                    'deepltranslate' => 1,
                ],
            ],
        ]);

        $translatedParent = $this->fetchTranslation('tx_testinlinerelations_parent', 1, 1);
        $this->assertIsArray($translatedParent);
        $this->assertSame(4, $this->countRecords('tx_testinlinerelations_child_declared'));
        foreach ([1, 2] as $childUid) {
            $translatedChild = $this->fetchTranslation('tx_testinlinerelations_child_declared', $childUid, 1);
            $this->assertIsArray($translatedChild, sprintf('Child %d has not been localized with its parent.', $childUid));
            $this->assertSame((int)$translatedParent['uid'], (int)$translatedChild['parentid'], sprintf('Child %d is not attached to the translated parent.', $childUid));
            $this->assertSame(self::EXAMPLE_TEXT['de'], $translatedChild['title'], sprintf('Child %d has not been translated by DeepL.', $childUid));
        }
    }

    /**
     * Flow 2, list module multi-selection: children and parent in one command map, children first.
     */
    #[Test]
    public function translatingChildrenAndParentInOneCommandMapAttachesAllTranslationsToTranslatedParent(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineRelationsParentLocalize.csv');

        $this->dispatchDeeplTranslateCommand([
            'tx_testinlinerelations_child_declared' => [
                1 => [
                    'deepltranslate' => 1,
                ],
                2 => [
                    'deepltranslate' => 1,
                ],
            ],
            'tx_testinlinerelations_parent' => [
                1 => [
                    'deepltranslate' => 1,
                ],
            ],
        ]);

        $translatedParent = $this->fetchTranslation('tx_testinlinerelations_parent', 1, 1);
        $this->assertIsArray($translatedParent);
        $this->assertSame(4, $this->countRecords('tx_testinlinerelations_child_declared'));
        foreach ([1, 2] as $childUid) {
            $translatedChild = $this->fetchTranslation('tx_testinlinerelations_child_declared', $childUid, 1);
            $this->assertIsArray($translatedChild, sprintf('Child %d has not been localized.', $childUid));
            $this->assertSame((int)$translatedParent['uid'], (int)$translatedChild['parentid'], sprintf('Child %d is not attached to the translated parent.', $childUid));
            $this->assertSame(self::EXAMPLE_TEXT['de'], $translatedChild['title'], sprintf('Child %d has not been translated by DeepL.', $childUid));
        }
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $commandMap
     */
    private function dispatchDeeplTranslateCommand(array $commandMap): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $commandMap);
        $dataHandler->process_cmdmap();

        $this->assertSame([], $dataHandler->errorLog);
    }

    /**
     * @return array<string, mixed>|false
     */
    private function fetchTranslation(string $table, int $defaultLanguageUid, int $languageId): array|false
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
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
