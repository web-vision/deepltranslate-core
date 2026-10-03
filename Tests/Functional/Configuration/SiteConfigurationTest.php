<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Configuration\SiteTcaConfiguration;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

/**
 * Covers the site configuration form without EXT:deepltranslate_mass and EXT:deepltranslate_auto_renew: the
 * extension brings its own DeepL tab holding the field of the translation context. With one of them installed, its
 * tab entry is used instead, which their own tests cover.
 */
final class SiteConfigurationTest extends AbstractDeepLTestCase
{
    #[Test]
    public function siteFormHasOneDeeplTabHoldingTheContext(): void
    {
        $siteTca = GeneralUtility::makeInstance(SiteTcaConfiguration::class)->getTca();
        $showItems = GeneralUtility::trimExplode(',', $siteTca['site']['types']['0']['showitem']);

        $deeplTabs = array_filter(
            $showItems,
            static fn(string $showItem): bool => str_starts_with($showItem, '--div--;') && stripos($showItem, 'deepl') !== false
        );
        $this->assertSame(
            ['--div--;LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:site_configuration.deepl.tab'],
            array_values($deeplTabs)
        );
        $deeplTabPosition = array_key_first($deeplTabs);
        $palettePosition = array_search('--palette--;;deepl', $showItems, true);
        $this->assertIsInt($palettePosition);
        $this->assertGreaterThan($deeplTabPosition, $palettePosition);
        foreach (array_slice($showItems, $deeplTabPosition + 1, $palettePosition - $deeplTabPosition - 1) as $showItem) {
            $this->assertStringStartsNotWith('--div--', $showItem, 'The palette must be placed in the DeepL tab');
        }
        $this->assertSame('deeplContext', $siteTca['site']['palettes']['deepl']['showitem']);
        $this->assertSame('text', $siteTca['site']['columns']['deeplContext']['config']['type']);
    }

    #[Test]
    public function deeplTabTitleIsResolved(): void
    {
        $languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');

        $this->assertSame(
            'DeepL',
            $languageService->sL('LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:site_configuration.deepl.tab')
        );
    }
}
