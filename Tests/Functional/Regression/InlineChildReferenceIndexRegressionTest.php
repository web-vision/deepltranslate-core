<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Regression;

use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

/**
 * The `deepltranslate` command localizes an inline child through its translated parent with a DataHandler of its
 * own. TYPO3 updates the reference index at the end of the outermost DataHandler only, the one started for the
 * child is never the outermost. The reference index has to contain the child translation and the relation of the
 * translated parent all the same. That DataHandler works with the backend user of the calling one, which is not
 * always the global one.
 */
final class InlineChildReferenceIndexRegressionTest extends AbstractDeepLTestCase
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

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/deepltranslate_core/Tests/Functional/Regression/Fixtures/Files' => 'fileadmin',
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

    #[Test]
    public function translatingFileReferenceOfTranslatedContentElementKeepsReferenceIndexComplete(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineFileReferenceReferenceIndex.csv');
        $this->updateReferenceIndex();

        $this->dispatchDeeplTranslateCommand('sys_file_reference', 1, 1);

        self::assertCSVDataSet(__DIR__ . '/Fixtures/Results/inlineFileReferenceReferenceIndexLocalized.csv');
        $this->assertReferenceIndexIsComplete();
    }

    #[Test]
    public function translatingInlineChildOfTranslatedParentKeepsReferenceIndexComplete(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineRelationsChildLocalize.csv');
        $this->updateReferenceIndex();

        $this->dispatchDeeplTranslateCommand('tx_testinlinerelations_child_declared', 3, 1);

        self::assertCSVDataSet(__DIR__ . '/Fixtures/Results/inlineRelationsChildDeclaredLocalized.csv');
        $this->assertReferenceIndexIsComplete();
    }

    /**
     * The calling DataHandler runs as an administrator, the global backend user may not edit the records. The
     * DataHandler localizing the child works with the user of the calling one, as TYPO3 does for its own.
     */
    #[Test]
    public function translatingInlineChildWorksWithBackendUserOfCallingDataHandler(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/beUserEditor.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineRelationsChildLocalize.csv');
        $administrator = $GLOBALS['BE_USER'];
        $this->setUpBackendUser(2);

        $this->dispatchDeeplTranslateCommand('tx_testinlinerelations_child_declared', 3, 1, $administrator);

        self::assertCSVDataSet(__DIR__ . '/Fixtures/Results/inlineRelationsChildDeclaredLocalized.csv');
    }

    /**
     * The data sets are imported without the reference index. It is built once before the command, so a check
     * afterwards reports only what the command missed.
     */
    private function updateReferenceIndex(): void
    {
        GeneralUtility::makeInstance(ReferenceIndex::class)->updateIndex(false);
    }

    private function assertReferenceIndexIsComplete(): void
    {
        $result = GeneralUtility::makeInstance(ReferenceIndex::class)->updateIndex(true);

        $this->assertSame([], $result['errors']);
    }

    private function dispatchDeeplTranslateCommand(
        string $table,
        int $uid,
        int $targetLanguageId,
        ?BackendUserAuthentication $backendUser = null
    ): void {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [
            $table => [
                $uid => [
                    'deepltranslate' => $targetLanguageId,
                ],
            ],
        ], $backendUser);
        $dataHandler->process_cmdmap();

        $this->assertSame([], $dataHandler->errorLog);
    }
}
