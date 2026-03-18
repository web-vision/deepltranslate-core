<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Updates;

use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;
use WebVision\Deepltranslate\Core\Upgrades\FormalityUpgradeWizard;

final class FormalityUpgradeWizardTest extends AbstractDeepLTestCase
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
        'EB' => [
            'id' => 3,
            'title' => 'Britisch',
            'locale' => 'en_GB',
            'iso' => 'eb',
            'hrefLang' => 'en-GB',
            'direction' => '',
            'custom' => [
                'deeplTargetLanguage' => 'EN-GB',
            ],
        ],
        'BS' => [
            'id' => 4,
            'title' => 'Bosnian',
            'locale' => 'bs_BA.utf8',
            'iso' => 'bs',
            'hrefLang' => 'bs',
            'direction' => '',
            'custom' => [
                'deeplTargetLanguage' => '',
            ],
        ],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = array_merge(
            $this->configurationToUseInTestInstance,
            require __DIR__ . '/../Fixtures/ExtensionConfig.php',
            [
                'EXTENSIONS' => [
                    'deepltranslate_core' => [
                        'deeplFormality' => 'default',
                    ],
                ],
            ]
        );

        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(rootPageId: 1),
            languages: [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('EB', '/eb/', ['EN'], 'strict'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'strict'),
                $this->buildLanguageConfiguration('BS', '/bs/', ['EN'], 'strict'),
            ],
        );
        $this->setUpFrontendRootPage(pageId: 1);
    }

    #[Test]
    public function executeSuccessMigrationProcess(): void
    {
        $wizard = GeneralUtility::makeInstance(FormalityUpgradeWizard::class);

        $outputMock = $this->createMock(OutputInterface::class);
        $outputMock->expects($this->any())
            ->method('writeln');

        $wizard->setOutput($outputMock);

        $executeUpdate = $wizard->executeUpdate();

        $this->assertTrue($executeUpdate, 'Upgrade process was failed');

        $siteConfiguration = GeneralUtility::makeInstance(SiteConfiguration::class);
        $loadedSiteConfiguration = $siteConfiguration->load('acme');

        $this->assertArrayHasKey('languages', $loadedSiteConfiguration);

        $this->assertArrayHasKey('deeplTargetLanguage', $loadedSiteConfiguration['languages'][1]);
        $this->assertArrayNotHasKey('deeplFormality', $loadedSiteConfiguration['languages'][1], 'EN become formality support');

        $this->assertArrayHasKey('deeplTargetLanguage', $loadedSiteConfiguration['languages'][2]);
        $this->assertArrayHasKey('deeplFormality', $loadedSiteConfiguration['languages'][2], 'DE became not "deeplFormality"');
        $this->assertEquals('default', $loadedSiteConfiguration['languages'][2]['deeplFormality'], 'DE became not formality support');
    }
}
