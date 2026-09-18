<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Core14\Backend\Localization;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Backend\Localization\LocalizationHandlerRegistry;
use TYPO3\CMS\Backend\Localization\LocalizationInstructions;
use TYPO3\CMS\Backend\Localization\LocalizationMode;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

/**
 * The TYPO3 v14 localization wizard used for an inline (IRRE) child of a translated parent: record uid 3
 * of `tx_testinlinerelations_child_declared` was added in the default language after its parent (uid 1)
 * had been translated (uid 2).
 *
 * The handlers are processed the way the wizard does after the editor picked one of them.
 */
#[Group('not-core-13')]
final class InlineChildLocalizationWizardTest extends AbstractDeepLTestCase
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
            'id' => 1,
            'title' => 'Deutsch',
            'locale' => 'de_DE',
            'iso' => 'de',
            'hrefLang' => 'de-DE',
            'direction' => '',
            'custom' => [
                'deeplTargetLanguage' => 'DE',
            ],
        ],
    ];

    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = __DIR__ . '/../../../Fixtures/Extensions/test_inline_relations';

        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../../../Regression/Fixtures/inlineRelationsChildLocalize.csv');
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(rootPageId: 1),
            languages: [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'strict'),
            ],
        );
        $this->setUpBackendUser(1);
        // The finishers of the wizard resolve their labels through `$GLOBALS['LANG']`, which the backend
        // request middleware provides in a real backend request.
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($GLOBALS['BE_USER']);
    }

    #[Test]
    public function wizardKeepsOfferingCoreManualAndDeeplForInlineChild(): void
    {
        $availableHandlers = $this->get(LocalizationHandlerRegistry::class)->getAvailableHandlers(
            new LocalizationInstructions('tx_testinlinerelations_child_declared', 3, 0, 1, LocalizationMode::TRANSLATE, [])
        );

        $this->assertArrayHasKey('manual', $availableHandlers);
        $this->assertArrayHasKey('deepltranslate', $availableHandlers);
    }

    #[Test]
    public function deeplHandlerTranslatesInlineChildAttachesItToTranslatedParentAndReloads(): void
    {
        $result = $this->get(LocalizationHandlerRegistry::class)->getHandler('deepltranslate')->processLocalization(
            new LocalizationInstructions('tx_testinlinerelations_child_declared', 3, 0, 1, LocalizationMode::TRANSLATE, [])
        )->jsonSerialize();

        $this->assertTrue($result['success']);
        $translatedChild = $this->fetchTranslation('tx_testinlinerelations_child_declared', 3, 1);
        $this->assertIsArray($translatedChild, 'Child has not been localized at all.');
        $this->assertSame(2, (int)$translatedChild['parentid'], 'Child translation is not attached to the translated parent.');
        $this->assertSame(self::EXAMPLE_TEXT['de'], $translatedChild['title'], 'Child translation has not been translated by DeepL.');
        $this->assertSame('reload', $result['finisher']['identifier'] ?? null, 'Editor is not kept in the parent form.');
    }

    #[Test]
    public function manualHandlerCopiesInlineChildAndAttachesItToTranslatedParent(): void
    {
        $result = $this->get(LocalizationHandlerRegistry::class)->getHandler('manual')->processLocalization(
            new LocalizationInstructions('tx_testinlinerelations_child_declared', 3, 0, 1, LocalizationMode::TRANSLATE, [])
        )->jsonSerialize();

        $this->assertTrue($result['success']);
        $translatedChild = $this->fetchTranslation('tx_testinlinerelations_child_declared', 3, 1);
        $this->assertIsArray($translatedChild, 'Child has not been localized at all.');
        $this->assertSame(2, (int)$translatedChild['parentid'], 'Child translation is not attached to the translated parent.');
        $this->assertSame(self::EXAMPLE_TEXT['en'], $translatedChild['title'], 'Manual localization must copy, not translate.');
        $this->assertSame('reload', $result['finisher']['identifier'] ?? null, 'Editor is not kept in the parent form.');
    }

    #[Test]
    public function manualHandlerKeepsFreeModeCopyOfInlineChildUnchanged(): void
    {
        $result = $this->get(LocalizationHandlerRegistry::class)->getHandler('manual')->processLocalization(
            new LocalizationInstructions('tx_testinlinerelations_child_declared', 3, 0, 1, LocalizationMode::COPY, [])
        )->jsonSerialize();

        $this->assertTrue($result['success']);
        $this->assertFalse($this->fetchTranslation('tx_testinlinerelations_child_declared', 3, 1), 'A free mode copy must not be connected to the default language record.');
        $this->assertSame(1, $this->countRecordsInLanguage('tx_testinlinerelations_child_declared', 1, 'proton beam'), 'Core free mode copy has not been created.');
    }

    /**
     * Two children were added after the parent had been translated. Localizing one of them must not localize
     * the other one as well.
     */
    #[Test]
    public function manualHandlerLocalizesOnlyTheRequestedOfTwoNewInlineChildren(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../../Fixtures/inlineRelationsSecondNewChild.csv');

        $result = $this->get(LocalizationHandlerRegistry::class)->getHandler('manual')->processLocalization(
            new LocalizationInstructions('tx_testinlinerelations_child_declared', 3, 0, 1, LocalizationMode::TRANSLATE, [])
        )->jsonSerialize();

        $this->assertTrue($result['success']);
        $translatedChild = $this->fetchTranslation('tx_testinlinerelations_child_declared', 3, 1);
        $this->assertIsArray($translatedChild, 'Requested child has not been localized.');
        $this->assertSame(2, (int)$translatedChild['parentid'], 'Requested child translation is not attached to the translated parent.');
        $this->assertFalse($this->fetchTranslation('tx_testinlinerelations_child_declared', 4, 1), 'Sibling child has been localized as well.');
    }

    /**
     * Same as {@see self::manualHandlerLocalizesOnlyTheRequestedOfTwoNewInlineChildren()} for DeepL, which is
     * localized through the parent by the `deepltranslate` DataHandler command (DPL-193).
     */
    #[Test]
    public function deeplHandlerTranslatesOnlyTheRequestedOfTwoNewInlineChildren(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../../Fixtures/inlineRelationsSecondNewChild.csv');

        $result = $this->get(LocalizationHandlerRegistry::class)->getHandler('deepltranslate')->processLocalization(
            new LocalizationInstructions('tx_testinlinerelations_child_declared', 3, 0, 1, LocalizationMode::TRANSLATE, [])
        )->jsonSerialize();

        $this->assertTrue($result['success']);
        $translatedChild = $this->fetchTranslation('tx_testinlinerelations_child_declared', 3, 1);
        $this->assertIsArray($translatedChild, 'Requested child has not been localized.');
        $this->assertSame(self::EXAMPLE_TEXT['de'], $translatedChild['title'], 'Requested child has not been translated by DeepL.');
        $this->assertFalse($this->fetchTranslation('tx_testinlinerelations_child_declared', 4, 1), 'Sibling child has been localized as well.');
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

    private function countRecordsInLanguage(string $table, int $languageId, string $title): int
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder
            ->count('uid')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($title, Connection::PARAM_STR)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne();
    }
}
