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
use WebVision\Deepltranslate\Core\Tests\Functional\Regression\Fixtures\Classes\PointProcessingInstructionToTranslationHook;

/**
 * Inline children and grandchildren localized along with their parent are translated from the language of the record
 * `DataHandler::localize()` copies, even when the processing instruction has been pointed to an already translated
 * record in between, as `web-vision/deepltranslate-auto-renew` does. A child of all languages (-1) is translated from
 * the default language.
 */
final class InlineChildSourceLanguageRegressionTest extends AbstractDeepLTestCase
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
        $this->testExtensionsToLoad[] = __DIR__ . '/../Fixtures/Extensions/test_inline_relations';

        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
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
    public function inlineChildrenAreTranslatedFromTheirOwnLanguageWhenProcessingInstructionPointsToTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineRelationsParentLocalize.csv');

        $languagePairs = $this->localizeParentWithDeepl();

        // Parent and four children, all of them in the default language.
        $this->assertSame(array_fill(0, 5, 'EN>DE'), $languagePairs);
        self::assertCSVDataSet(__DIR__ . '/Fixtures/Results/inlineRelationsParentLocalized.csv');
    }

    #[Test]
    public function allLanguagesAndNestedInlineChildrenAreTranslatedFromDefaultLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineRelationsNestedParentLocalize.csv');

        $languagePairs = $this->localizeParentWithDeepl();

        // Parent, two children and two grandchildren. The second grandchild follows the translation of the first one,
        // the child of all languages (-1) the translation of the first child.
        $this->assertSame(array_fill(0, 5, 'EN>DE'), $languagePairs);
        self::assertCSVDataSet(__DIR__ . '/Fixtures/Results/inlineRelationsNestedParentLocalized.csv');
    }

    /**
     * Localizes the parent record 1 to German with DeepL, while the processing instruction is pointed to every
     * created translation of an inline child or grandchild.
     *
     * @return list<string> the language pairs requested from DeepL, `EN>DE` for example
     */
    private function localizeParentWithDeepl(): array
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
            = PointProcessingInstructionToTranslationHook::class;
        // The DeepL mock server ignores the source language, the requested language pairs are recorded instead.
        $languagePairs = [];
        $this->get('service_container')->set(
            'inline-child-source-language-listener',
            static function (DeepLGlossaryIdEvent $event) use (&$languagePairs): void {
                $languagePairs[] = $event->sourceLanguage . '>' . $event->targetLanguage;
            }
        );
        $this->get(ListenerProvider::class)->addListener(DeepLGlossaryIdEvent::class, 'inline-child-source-language-listener');

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [
            'tx_testinlinerelations_parent' => [
                1 => [
                    'deepltranslate' => 1,
                ],
            ],
        ]);
        $dataHandler->process_cmdmap();

        $this->assertSame([], $dataHandler->errorLog);
        return $languagePairs;
    }
}
