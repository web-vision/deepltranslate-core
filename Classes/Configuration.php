<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

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

    public function isDeeplTranslateAllowed(int $pageId): bool
    {
        $pageTsConfig = BackendUtility::getPagesTSconfig($pageId);
        if (!is_array($pageTsConfig['mod.'] ?? null)
            || !is_array($pageTsConfig['mod.']['web_layout.'] ?? null)
            || !is_array($pageTsConfig['mod.']['web_layout.']['localization.'] ?? null)
            || !(
                is_bool($pageTsConfig['mod.']['web_layout.']['localization.']['enableDeeplTranslate'] ?? null)
                || is_int($pageTsConfig['mod.']['web_layout.']['localization.']['enableDeeplTranslate'] ?? null)
                || (
                    is_string($pageTsConfig['mod.']['web_layout.']['localization.']['enableDeeplTranslate'] ?? null)
                    && MathUtility::canBeInterpretedAsInteger($pageTsConfig['mod.']['web_layout.']['localization.']['enableDeeplTranslate'])
                )
            )
        ) {
            return true;
        }
        return (bool)$pageTsConfig['mod.']['web_layout.']['localization.']['enableDeeplTranslate'];
    }

    public function isCoreTranslationDisabled(int $pageId): bool
    {
        $pageTsConfig = BackendUtility::getPagesTSconfig($pageId);
        if (!is_array($pageTsConfig['mod.'] ?? null)
            || !is_array($pageTsConfig['mod.']['web_layout.'] ?? null)
            || !is_array($pageTsConfig['mod.']['web_layout.']['localization.'] ?? null)
            || !(
                is_bool($pageTsConfig['mod.']['web_layout.']['localization.']['disableCoreTranslation'] ?? null)
                || is_int($pageTsConfig['mod.']['web_layout.']['localization.']['disableCoreTranslation'] ?? null)
                || (
                    is_string($pageTsConfig['mod.']['web_layout.']['localization.']['disableCoreTranslation'] ?? null)
                    && MathUtility::canBeInterpretedAsInteger($pageTsConfig['mod.']['web_layout.']['localization.']['disableCoreTranslation'])
                )
            )
        ) {
            return false;
        }
        return (bool)$pageTsConfig['mod.']['web_layout.']['localization.']['disableCoreTranslation'];
    }
}
