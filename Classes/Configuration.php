<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core;

use DeepL\TranslatorOptions;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsAlias(id: ConfigurationInterface::class, public: true)]
#[AsAlias(id: 'deepl-core.configuration', public: true)]
final class Configuration implements ConfigurationInterface
{
    private string $apiKey;
    private string $modelType;
    private string $splitSentences;
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
    private \GuzzleHttp\ClientInterface $httpClient;

    // only for test environment, not for usage in Production environment
    private string $serverUrl;
    /**
     * only for the test environment, not for usage in the Production environment
     *
     * @var array<string|int, string>
     */
    private array $headers;

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    public function __construct()
    {
        // @todo Consider to move this into a dedicated factory and let the values directly passed and set as CPP,
        //       at best using native PHP enums.
        // @todo That is a service AND should be injected and not created (if still needed in the future)
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('deepltranslate_core');

        $this->apiKey = (string)($extensionConfiguration['apiKey'] ?? '');
        $this->modelType = (string)($extensionConfiguration['modelType'] ?? 'prefer_quality_optimized');
        $this->splitSentences = ($extensionConfiguration['splitSentences'] ?? 'on');
        $this->preserveFormatting = (bool)($extensionConfiguration['preserverFormatting'] ?? false);
        $this->ignoreTags = GeneralUtility::trimExplode(',', ($extensionConfiguration['ignoreTags'] ?? ''), true);
        $this->nonSplittingTags = GeneralUtility::trimExplode(',', ($extensionConfiguration['nonSplittingTags'] ?? ''), true);
        $this->splittingTags = GeneralUtility::trimExplode(',', ($extensionConfiguration['splittingTags'] ?? ''), true);
        $this->outlineDetection = (bool)($extensionConfiguration['outlineDetection'] ?? true);
        // @todo Should this really be a UI extension configuration ?
        $this->serverUrl = (string)($extensionConfiguration['serverUrl'] ?? '');
        // @todo Should this really be a UI extension configuraiton ?
        $this->headers = (array)($extensionConfiguration['headers'] ?? []);
        // @todo That is a service AND should be injected and not created.
        $this->httpClient = GeneralUtility::makeInstance(GuzzleClientFactory::class)->getClient();
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getModelType(): string
    {
        return $this->modelType;
    }

    public function getSplitSentences(): string
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

    /**
     * @return array<TranslatorOptions::*, mixed>
     */
    public function getConfigurationForDeepLClient(): array
    {
        $configurationArray = [
            TranslatorOptions::HTTP_CLIENT => $this->httpClient,
        ];
        if ((GeneralUtility::makeInstance(ApplicationContext::class))->isTesting()) {
            $configurationArray = array_merge($configurationArray, [
                TranslatorOptions::SERVER_URL => $this->serverUrl,
                TranslatorOptions::HEADERS => $this->headers,
            ]);
        }
        return $configurationArray;
    }

    /**
     * @return array<TranslatorOptions::*, mixed>
     */
    public function getConfigurationForTranslation(): array
    {
        return [];
    }
}
