<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Core14\Backend\Localization;

use PHPUnit\Framework\Attributes\DataProvider;
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
 * The TYPO3 v14 localization wizard used for an inline (IRRE) child of a translated parent: records uid 3
 * and 4 of `tx_testinlinerelations_child_declared` were added in the default language after their parent
 * (uid 1) had been translated (uid 2).
 *
 * Child 5 belongs to a parent without translation (uid 3), child 6 to a parent whose only record in the
 * target language is a free mode copy (uid 4). Parent uid 6 is a record without inline relation.
 * Grandchild 3 (`tx_testinlinerelations_grandchild`) is a new child of child 1, nested one level deeper.
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineChildLocalizationWizard.csv');
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

    public static function wizardRecordDataProvider(): \Generator
    {
        yield 'inline child' => ['tx_testinlinerelations_child_declared', 3];
        yield 'record without inline relation' => ['tx_testinlinerelations_parent', 6];
        yield 'page' => ['pages', 1];
    }

    /**
     * The wizard preselects the first handler. The decorator of core's "Manual Translation" handler must
     * not move it behind "Translate with DeepL".
     */
    #[Test]
    #[DataProvider('wizardRecordDataProvider')]
    public function wizardOffersCoreManualFirstAndDeeplSecond(string $table, int $uid): void
    {
        $availableHandlers = $this->get(LocalizationHandlerRegistry::class)->getAvailableHandlers(
            new LocalizationInstructions($table, $uid, 0, 1, LocalizationMode::TRANSLATE, [])
        );

        $this->assertSame(['manual', 'deepltranslate'], array_keys($availableHandlers));
    }

    public static function handlerDataProvider(): \Generator
    {
        yield 'Translate with DeepL' => ['deepltranslate', self::EXAMPLE_TEXT['de']];
        yield 'Manual Translation' => ['manual', self::EXAMPLE_TEXT['en']];
    }

    /**
     * The handler redirects to the edit form of the translated parent. The redirect goes through the content
     * container of the backend, which asks before unsaved changes of an open edit form are discarded.
     */
    #[Test]
    #[DataProvider('handlerDataProvider')]
    public function handlerLocalizesInlineChildThroughTranslatedParentAndRedirectsToItsEditForm(string $handler, string $expectedTitle): void
    {
        $result = $this->processLocalization($handler, 'tx_testinlinerelations_child_declared', 3);

        $this->assertTrue($result['success']);
        $translatedChild = $this->fetchTranslation('tx_testinlinerelations_child_declared', 3, 1);
        $this->assertIsArray($translatedChild, 'Child has not been localized at all.');
        $this->assertSame(2, (int)$translatedChild['parentid'], 'Child translation is not attached to the translated parent.');
        $this->assertSame($expectedTitle, $translatedChild['title']);
        $this->assertFalse($this->fetchTranslation('tx_testinlinerelations_child_declared', 4, 1), 'Sibling child has been localized as well.');
        $this->assertSame('redirect', $result['finisher']['identifier'] ?? null);
        $redirectUrl = (string)($result['finisher']['data']['url'] ?? '');
        $this->assertStringEndsWith('/record/edit', (string)parse_url($redirectUrl, PHP_URL_PATH));
        $redirectParameters = $this->getQueryParameters($redirectUrl);
        $this->assertSame(['tx_testinlinerelations_parent' => [2 => 'edit']], $redirectParameters['edit'] ?? null, 'Editor is not sent to the edit form of the translated parent.');
        $returnParameters = $this->getQueryParameters((string)($redirectParameters['returnUrl'] ?? ''));
        $this->assertSame(['1', ['1']], [$returnParameters['id'] ?? null, $returnParameters['languages'] ?? null], 'Edit form does not return to the page module in the target language.');
    }

    /**
     * Grandchild 3 belongs to child 1, which belongs to parent 1. The edit form opened for it is the one of
     * the top-most record, the translated parent 2.
     */
    #[Test]
    #[DataProvider('handlerDataProvider')]
    public function handlerLocalizesNestedInlineChildAndRedirectsToEditFormOfTopMostTranslatedParent(string $handler, string $expectedTitle): void
    {
        $result = $this->processLocalization($handler, 'tx_testinlinerelations_grandchild', 3);

        $this->assertTrue($result['success']);
        $translatedGrandchild = $this->fetchTranslation('tx_testinlinerelations_grandchild', 3, 1);
        $this->assertIsArray($translatedGrandchild, 'Grandchild has not been localized at all.');
        $this->assertSame(2, (int)$translatedGrandchild['childid'], 'Grandchild translation is not attached to the translated child.');
        $this->assertSame($expectedTitle, $translatedGrandchild['title']);
        $this->assertSame('redirect', $result['finisher']['identifier'] ?? null);
        $redirectParameters = $this->getQueryParameters((string)($result['finisher']['data']['url'] ?? ''));
        $this->assertSame(['tx_testinlinerelations_parent' => [2 => 'edit']], $redirectParameters['edit'] ?? null, 'Editor is not sent to the edit form of the top-most translated record.');
    }

    #[Test]
    #[DataProvider('handlerDataProvider')]
    public function handlerRefusesInlineChildOfUntranslatedParent(string $handler): void
    {
        $result = $this->processLocalization($handler, 'tx_testinlinerelations_child_declared', 5);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('has no translation in this language', implode(' ', $result['errors'] ?? []));
        $this->assertFalse($this->fetchTranslation('tx_testinlinerelations_child_declared', 5, 1), 'Child of an untranslated parent has been localized.');
    }

    /**
     * `inlineLocalizeSynchronize` returns without an error when the only record of the parent in the target
     * language is a free mode copy, nothing is created.
     */
    #[Test]
    #[DataProvider('handlerDataProvider')]
    public function handlerReportsErrorWhenInlineChildHasNotBeenLocalized(string $handler): void
    {
        $result = $this->processLocalization($handler, 'tx_testinlinerelations_child_declared', 6);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('has not been localized', implode(' ', $result['errors'] ?? []));
        $this->assertFalse($this->fetchTranslation('tx_testinlinerelations_child_declared', 6, 1));
    }

    #[Test]
    #[DataProvider('handlerDataProvider')]
    public function handlerLeavesAlreadyTranslatedInlineChildAlone(string $handler): void
    {
        $result = $this->processLocalization($handler, 'tx_testinlinerelations_child_declared', 1);

        $this->assertTrue($result['success']);
        $this->assertSame('noop', $result['finisher']['identifier'] ?? null);
        $this->assertSame(1, $this->countRecordsInLanguage('tx_testinlinerelations_child_declared', 1, 'Protonenstrahl'), 'Child has been localized a second time.');
    }

    #[Test]
    public function manualHandlerKeepsFreeModeCopyOfInlineChildUnchanged(): void
    {
        $result = $this->processLocalization('manual', 'tx_testinlinerelations_child_declared', 3, LocalizationMode::COPY);

        $this->assertTrue($result['success']);
        $this->assertFalse($this->fetchTranslation('tx_testinlinerelations_child_declared', 3, 1), 'A free mode copy must not be connected to the default language record.');
        $this->assertSame(1, $this->countRecordsInLanguage('tx_testinlinerelations_child_declared', 1, 'proton beam'), 'Core free mode copy has not been created.');
    }

    /**
     * Records which are not inline children, pages among them, are handed to core's handler unchanged.
     */
    public static function otherRecordDataProvider(): \Generator
    {
        yield 'record without inline relation' => ['tx_testinlinerelations_parent', 6];
        yield 'page' => ['pages', 1];
    }

    #[Test]
    #[DataProvider('otherRecordDataProvider')]
    public function manualHandlerHandsOtherRecordsToCore(string $table, int $uid): void
    {
        $result = $this->processLocalization('manual', $table, $uid);

        $this->assertTrue($result['success']);
        $translation = $this->fetchTranslation($table, $uid, 1);
        $this->assertIsArray($translation, 'Core has not localized the record.');
        $this->assertSame(1, (int)$translation['sys_language_uid']);
    }

    /**
     * @return array<string, mixed>
     */
    private function processLocalization(string $handler, string $table, int $uid, LocalizationMode $mode = LocalizationMode::TRANSLATE): array
    {
        return $this->get(LocalizationHandlerRegistry::class)->getHandler($handler)->processLocalization(
            new LocalizationInstructions($table, $uid, 0, 1, $mode, [])
        )->jsonSerialize();
    }

    /**
     * @return array<array-key, mixed>
     */
    private function getQueryParameters(string $url): array
    {
        parse_str((string)parse_url($url, PHP_URL_QUERY), $queryParameters);

        return $queryParameters;
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
