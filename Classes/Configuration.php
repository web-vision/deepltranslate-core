<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsAlias(id: ConfigurationInterface::class, public: true)]
final class Configuration implements ConfigurationInterface
{
    private string $apiKey;
    private string $modelType;
    private bool $splitSentences;
    private bool $preserveFormatting;

    /**
     * @var string[]
     */
    private array $ignoreTags;

    /**
     * @var string[]
     */
    private array $nonSplittingTags;

    /**
     * @var string[]
     */
    private array $splittingTags;
    private bool $outlineDetection;

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    public function __construct()
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('deepltranslate_core');

        $this->apiKey = (string)($extensionConfiguration['apiKey'] ?? '');
        $this->modelType = (string)($extensionConfiguration['modelType'] ?? 'prefer_quality_optimized');
        $this->splitSentences = (bool)($extensionConfiguration['splitSentences'] ?? true);
        $this->preserveFormatting = (bool)($extensionConfiguration['preserverFormatting'] ?? false);
        $this->ignoreTags = GeneralUtility::trimExplode(',', ($extensionConfiguration['ignoreTags'] ?? ''), true);
        $this->nonSplittingTags = GeneralUtility::trimExplode(',', ($extensionConfiguration['nonSplittingTags'] ?? ''), true);
        $this->splittingTags = GeneralUtility::trimExplode(',', ($extensionConfiguration['splittingTags'] ?? ''), true);
        $this->outlineDetection = (bool)($extensionConfiguration['outlineDetection'] ?? true);
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getModelType(): string
    {
        return $this->modelType;
    }

    public function isSplitSentenceEnabled(): bool
    {
        return $this->splitSentences;
    }

    public function isPreserveFormattingEnabled(): bool
    {
        return $this->preserveFormatting;
    }

    public function getIgnoreTags(): string
    {
        return implode(',', $this->ignoreTags);
    }

    public function getNonSplittingTags(): string
    {
        return implode(',', $this->nonSplittingTags);
    }

    public function getSplittingTags(): string
    {
        return implode(',', $this->splittingTags);
    }

    public function isOutlineDetectionEnabled(): bool
    {
        return $this->outlineDetection;
    }
}
