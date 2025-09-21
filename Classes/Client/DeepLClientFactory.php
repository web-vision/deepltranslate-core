<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Client;

use DeepL\TranslatorOptions;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use WebVision\Deepltranslate\Core\ClientInterface as DeepltranslateCoreClientInterface;
use WebVision\Deepltranslate\Core\ConfigurationInterface;

/**
 * Default factory implementation for {@see DeepLClientFactoryInterface} creating clients for
 * context-aware based {@see DeepltranslateCoreClientInterface}. Could be used in context class
 * constructor's so use `$context` on methods only for class checks and not to call methods.
 *
 * Don't use it directly, use the interface instead. For example:
 *
 * ```
 * <?php
 *
 * namespace Vendor\Extension\Client;
 *
 * use WebVision\Deepltranslate\Core\Client\DeepLClientFactoryInterface;
 * use WebVision\Deepltranslate\Core\Client\DeepLClientInterface;
 *
 * final readonly SomeClient implements DeepLClientInterface {
 *
 *     private DeepLClientInterface $client;
 *
 *     public function __construct(
 *         private DeepLClientFactoryInterface $clientFactory,
 *     ) {
 *         $this->client = $clientFactory->create($this);
 *     }
 * }
 * ```
 *
 * @internal for `EXT:deepltranslate_*` internal testing purpose and not part of public API.
 */
#[AsAlias(id: DeepLClientFactoryInterface::class, public: true)]
final class DeepLClientFactory implements DeepLClientFactoryInterface
{
    public function __construct(
        private ConfigurationInterface $defaultConfiguration,
        private GuzzleClientFactory $clientFactory,
    ) {}

    /**
     * Create native DeepLClient based on passed $configuration or some default, for
     * example using default `EXT:deepltranslate_core` extension configuration.
     *
     * @param DeepltranslateCoreClientInterface $context        Context class to build client for, which must be an
     *                                                          instance of DeepltranslateCoreClientInterface interface.
     * @param ConfigurationInterface|null       $configuration  (internal) `EXT:deepltranslate_core` compatible configuration
     *                                                          class, using default configuration if not provided. Can be
     *                                                          used to pass custom instances down from decorating factories.
     * @param GuzzleClientInterface|null        $client         (internal) Compatible guzzle client instance to use, otherwise
     *                                                          default instance will be created and configured.
     * @param array<string, mixed>|null         $options        (internal) Override options instead of building own options
     *                                                          useable from decorating factories, for example.
     */
    public function create(
        DeepltranslateCoreClientInterface $context,
        ?ConfigurationInterface $configuration = null,
        ?GuzzleClientInterface $client = null,
        ?array $options = null,
    ): DeepLClientInterface {
        $configuration ??= $this->defaultConfiguration;
        $client ??= $this->clientFactory->getClient();
        $options = $this->buildDeepLClientOptions($context, $configuration, $client, $options);
        return new DeepLAPIClient(
            $configuration->getApiKey(),
            $options,
        );
    }

    /**
     * Build options for client, possible using `$context` to decide context aware configuration.
     *
     * @param DeepltranslateCoreClientInterface $context        Context class to build client for, which must be an
     *                                                          instance of DeepltranslateCoreClientInterface interface.
     * @param ConfigurationInterface            $configuration  (internal) `EXT:deepltranslate_core` compatible configuration
     *                                                          class, using default configuration if not provided. Can be
     *                                                          used to pass custom instances down from decorating factories.
     * @param GuzzleClientInterface             $client         (internal) Compatible guzzle client instance to use, otherwise
     * *                                                        default instance will be created and configured.
     * @param array<string, mixed>|null         $options        (internal) Override options instead of building own options
     *                                                          useable from decorating factories, for example.
     * @return array<string, mixed>
     */
    public function buildDeepLClientOptions(
        DeepltranslateCoreClientInterface $context,
        ConfigurationInterface $configuration,
        GuzzleClientInterface $client,
        ?array $options = null,
    ): array {
        $options ??= [];
        $options[TranslatorOptions::HTTP_CLIENT] ??= $client;
        return $options;
    }
}
