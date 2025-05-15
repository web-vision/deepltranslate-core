<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsAlias(id: ConfigurationInterface::class, public: true)]
final class Configuration implements ConfigurationInterface
{
    private string $apiKey = '';

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    public function __construct()
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('deepltranslate_core');

        $this->apiKey = (string)($extensionConfiguration['apiKey'] ?? '');
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Checks translation allowed against Page TSconfig from settings
     *
     * @param int $pageId
     * @return bool
     * @throws \JsonException
     */
    public function isDeeplTranslationAllowedOnPage(int $pageId): bool
    {
        $localizationConfiguration = BackendUtility::getPagesTSconfig($pageId)['mod.']['web_layout.']['localization.'] ?? [];
        if ((bool)($localizationConfiguration['enableDeeplTranslate'] ?? true) === false) {
            return false;
        }
        return true;
    }
}
