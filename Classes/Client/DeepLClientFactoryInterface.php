<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Client;

use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\ClientInterface as GuzzleClientInterfaceAlias;
use WebVision\Deepltranslate\Core\ClientInterface as DeepltranslateCoreClientInterface;
use WebVision\Deepltranslate\Core\ConfigurationInterface;

/**
 * Describes factory implementation for {@see DeepLClientFactoryInterface} creating clients
 * context-aware based on {@see DeepltranslateCoreClientInterface} instances. Could be used
 * in context class constructor's so use `$context` on methods only for class checks and not
 * to call methods.
 *
 * @internal for `EXT:deepltranslate_*` internal testing purpose and not part of public API.
 */
interface DeepLClientFactoryInterface
{
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
        ?GuzzleClientInterfaceAlias $client = null,
        ?array $options = null,
    ): DeepLClientInterface;

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
    ): array;
}
