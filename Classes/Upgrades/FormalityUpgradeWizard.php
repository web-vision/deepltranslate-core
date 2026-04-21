<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Upgrades;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use WebVision\Deepltranslate\Core\Service\DeeplService;

#[UpgradeWizard(identifier: 'wvDeepltranslate_formalityUpgrade')]
#[Autoconfigure(public: true)]
class FormalityUpgradeWizard implements UpgradeWizardInterface, ChattyInterface
{
    protected OutputInterface $output;

    public function __construct(
        private DeeplService $deeplService,
        private SiteWriter $siteWriter,
    ) {}

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    public function getIdentifier(): string
    {
        return 'wvDeepltranslate_formalityUpgrade';
    }

    public function getTitle(): string
    {
        return 'Move Formality Configuration to SiteConfiguration';
    }

    public function getDescription(): string
    {
        return 'Migrates global extension configuration formality to all SiteConfigurations with a DeepL target language and respects target on support formality';
    }

    public function updateNecessary(): bool
    {
        return true;
    }

    public function executeUpdate(): bool
    {
        $globalFormality = 'default';
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['deepltranslate_core']['deeplFormality'])) {
            $globalFormality = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['deepltranslate_core']['deeplFormality'];
        }

        try {
            $sitePath = Environment::getConfigPath() . '/sites';
            if (!is_dir($sitePath)) {
                return false;
            }

            $finder = new Finder();
            $finder->in($sitePath)->files()->name('config.yaml');

            /** @var \SplFileInfo $file */
            foreach ($finder as $file) {
                $loadedSiteConfiguration = Yaml::parse((string)file_get_contents($file->getRealPath()));

                if (!isset($loadedSiteConfiguration['languages'])) {
                    continue;
                }

                foreach ($loadedSiteConfiguration['languages'] as &$language) {
                    if (isset($language['deeplFormality'])) {
                        continue;
                    }

                    if (isset($language['deeplTargetLanguage'])
                        && $language['deeplTargetLanguage'] !== ''
                        && $this->deeplService->hasLanguageFormalitySupport($language['deeplTargetLanguage'])
                    ) {
                        $language['deeplFormality'] = $globalFormality;
                    }
                }

                $explodedSiteConfigPath = explode(DIRECTORY_SEPARATOR, $file->getPath());
                $siteIdentifier = array_pop($explodedSiteConfigPath);
                $this->siteWriter->write($siteIdentifier, $loadedSiteConfiguration);
            }

            return true;
        } catch (\Exception $exception) {
            $this->output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
            return false;
        }
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }
}
