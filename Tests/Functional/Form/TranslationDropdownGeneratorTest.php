<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Form;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Site\SiteFinder;
use WebVision\Deepltranslate\Core\Core13\EventListener\RenderLocalizationSelect;
use WebVision\Deepltranslate\Core\Form\TranslationDropdownGenerator;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

/**
 * The languages offered by the DeepL dropdown of the list module for page 1, which has no translation yet.
 *
 * The dropdown is rendered by the TYPO3 v13 listener {@see RenderLocalizationSelect} only, TYPO3 v14 has no
 * consumer of the generator.
 */
#[Group('not-core-14')]
final class TranslationDropdownGeneratorTest extends AbstractDeepLTestCase
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
            'id' => 2,
            'title' => 'Deutsch',
            'locale' => 'de_DE',
            'iso' => 'de',
            'hrefLang' => 'de-DE',
            'direction' => '',
            'custom' => [
                'deeplTargetLanguage' => 'DE',
            ],
        ],
        'FR' => [
            'id' => 3,
            'title' => 'Français',
            'locale' => 'fr_FR',
            'iso' => 'fr',
            'hrefLang' => 'fr-FR',
            'direction' => '',
            'custom' => [
                'deeplTargetLanguage' => 'FR',
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/translationDropdownGenerator.csv');
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(rootPageId: 1),
            languages: [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'strict'),
                $this->buildLanguageConfiguration('FR', '/fr/', ['EN'], 'strict'),
            ],
        );
    }

    public static function offeredLanguagesDataProvider(): \Generator
    {
        yield 'admin' => [
            'backendUserUid' => 1,
            'expectedLanguageTitles' => ['Deutsch', 'Français'],
        ];
        yield 'editor without language restriction' => [
            'backendUserUid' => 2,
            'expectedLanguageTitles' => ['Deutsch', 'Français'],
        ];
        yield 'editor without access to German' => [
            'backendUserUid' => 3,
            'expectedLanguageTitles' => ['Français'],
        ];
    }

    /**
     * @param list<string> $expectedLanguageTitles
     */
    #[Test]
    #[DataProvider('offeredLanguagesDataProvider')]
    public function buildTranslateDropdownOptionsOffersTheLanguagesTheUserMayEdit(
        int $backendUserUid,
        array $expectedLanguageTitles,
    ): void {
        $this->setUpBackendUser($backendUserUid);
        $siteLanguages = $this->get(SiteFinder::class)->getSiteByIdentifier('acme')->getLanguages();

        $options = $this->get(TranslationDropdownGenerator::class)
            ->buildTranslateDropdownOptions($siteLanguages, 1, '/typo3/module/web/list?id=1');

        preg_match_all('#<option value="[^"]+">([^<]*)</option>#', $options, $matches);
        $this->assertSame($expectedLanguageTitles, $matches[1]);
    }
}
