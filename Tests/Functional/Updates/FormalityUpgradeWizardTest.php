<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Updates;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;
use WebVision\Deepltranslate\Core\Tests\Functional\Fixtures\Traits\SiteBasedTestTrait;
use WebVision\Deepltranslate\Core\Upgrades\FormalityUpgradeWizard;

class FormalityUpgradeWizardTest extends AbstractDeepLTestCase
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
            'acme',
            $this->buildSiteConfiguration(1, '/', 'Home'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('EB', '/eb/', ['EN'], 'strict'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'strict'),
                $this->buildLanguageConfiguration('BS', '/bs/', ['EN'], 'strict'),
            ]
        );
        $this->setUpFrontendRootPage(1, [], []);
    }

    #[Test]
    public function executeSuccessMigrationProcess(): void
    {
        $wizard = GeneralUtility::makeInstance(FormalityUpgradeWizard::class);

        $outputMock = $this->createMock(OutputInterface::class);
        $outputMock->expects(static::any())
            ->method('writeln');

        $wizard->setOutput($outputMock);

        $executeUpdate = $wizard->executeUpdate();

        static::assertTrue($executeUpdate, 'Upgrade process was failed');

        $siteConfiguration = GeneralUtility::makeInstance(SiteConfiguration::class);
        $loadedSiteConfiguration = $siteConfiguration->load('acme');

        static::assertArrayHasKey('languages', $loadedSiteConfiguration);

        static::assertArrayHasKey('deeplTargetLanguage', $loadedSiteConfiguration['languages'][1]);
        static::assertArrayNotHasKey('deeplFormality', $loadedSiteConfiguration['languages'][1], 'EN become formality support');

        static::assertArrayHasKey('deeplTargetLanguage', $loadedSiteConfiguration['languages'][2]);
        static::assertArrayHasKey('deeplFormality', $loadedSiteConfiguration['languages'][2], 'DE became not "deeplFormality"');
        static::assertEquals('default', $loadedSiteConfiguration['languages'][2]['deeplFormality'], 'DE became not formality support');
    }
}
