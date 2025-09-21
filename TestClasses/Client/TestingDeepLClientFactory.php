<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Testing\Client;

use DeepL\TranslatorOptions;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use WebVision\Deepltranslate\Core\Client\DeepLClientFactoryInterface;
use WebVision\Deepltranslate\Core\Client\DeepLClientInterface;
use WebVision\Deepltranslate\Core\ClientInterface as DeepltranslateCoreClientInterface;
use WebVision\Deepltranslate\Core\ConfigurationInterface;

/**
 * Decorates {@see DeepLClientFactoryInterface} to set some options only in `TESTING` TYPO3 Context
 * when creating clients for {@see DeepltranslateCoreClientInterface} using the decorated factory
 * under the hood to reduce duplicated code. Could be used in context class constructor's so use
 * `$context` on methods only for class checks and not to call methods.
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
#[AsDecorator(
    decorates: DeepLClientFactoryInterface::class,
    priority: 1000,
    onInvalid: ContainerInterface::IGNORE_ON_INVALID_REFERENCE,
)]
final class TestingDeepLClientFactory implements DeepLClientFactoryInterface
{
    public function __construct(
        #[AutowireDecorated]
        private DeepLClientFactoryInterface $previousClientFactory,
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
        if (!Environment::getContext()->isTesting()) {
            // Short circuit passing down to decorated factory if not in `TESTING` TYPO3 Context. Should
            // not happen and acts as a safeguard for not correctly setup testing environments.
            return $this->previousClientFactory->create($context, $configuration, $client, $options);
        }
        $configuration ??= $this->defaultConfiguration;
        $client ??= $this->clientFactory->getClient();
        $options = $this->buildDeepLClientOptions($context, $configuration, $client, $options);
        return $this->previousClientFactory->create($context, $configuration, $client, $options);
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
        $options = $this->previousClientFactory->buildDeepLClientOptions($context, $configuration, $client, $options);
        if (Environment::getContext()->isTesting()) {
            // In `TESTING` context override following options if set in global variables, unrelated if
            // prepared options has been passed down or not.
            $serverUrl = $GLOBALS['DEEPL_TESTING']['SERVER_URL'] ?? null;
            $headers = $GLOBALS['DEEPL_TESTING']['HEADERS'] ?? null;
            if ($serverUrl !== null) {
                $options[TranslatorOptions::SERVER_URL] = $serverUrl;
            }
            if ($headers !== null) {
                $options[TranslatorOptions::HEADERS] = $headers;
            }
        }
        return $options;
    }
}
