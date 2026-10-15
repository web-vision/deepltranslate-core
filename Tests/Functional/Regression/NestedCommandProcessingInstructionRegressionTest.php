<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Regression;

use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Event\DeepLGlossaryIdEvent;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;
use WebVision\Deepltranslate\Core\Tests\Functional\Regression\Fixtures\Classes\IssueNestedCommandHook;

/**
 * A `deepltranslate` command issued while another record is still being localized, as
 * `web-vision/deepltranslate-auto-renew` does from its DataHandler hooks, restores the processing instruction of the
 * running localization when it is done. The remaining fields of that localization keep the page of their record, the
 * page the glossary is taken from.
 */
final class NestedCommandProcessingInstructionRegressionTest extends AbstractDeepLTestCase
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
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineRelationsNestedCommandLocalize.csv');
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(rootPageId: 1),
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
    public function remainingFieldsKeepTheirPageAfterNestedCommandForRecordOnOtherPage(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
            = IssueNestedCommandHook::class;
        // The page a DeepL request is made for, the page the glossary is looked up for.
        $requestedPages = [];
        $this->get('service_container')->set(
            'nested-command-processing-instruction-listener',
            static function (DeepLGlossaryIdEvent $event) use (&$requestedPages): void {
                $requestedPages[] = $event->currentPage?->uid;
            }
        );
        $this->get(ListenerProvider::class)->addListener(DeepLGlossaryIdEvent::class, 'nested-command-processing-instruction-listener');

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [
            'tx_testinlinerelations_parent' => [
                1 => [
                    'deepltranslate' => 1,
                ],
            ],
        ]);
        $dataHandler->process_cmdmap();

        static::assertSame([], $dataHandler->errorLog);
        // Parent 1 and its first child, the nested command for parent 10 on page 2 issued when the translation of
        // the first child is saved, then the second child of parent 1.
        static::assertSame([1, 1, 2, 1], $requestedPages);
        self::assertCSVDataSet(__DIR__ . '/Fixtures/Results/inlineRelationsNestedCommandLocalized.csv');
    }
}
