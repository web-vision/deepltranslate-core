<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Client;

use DeepL\DeepLClient as OfficialDeepLClient;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Wrapper class for the DeepL PHP API.
 *
 * This class ensures correct behaviour between the DeepL PHP AIP and uor extension, as it implements the {@see DeepLClientInterface}
 * and the {@see OfficialDeepLClient}. THis is a workaround for early detection of changes inside the DeepL client.
 */
#[AsAlias(id: DeepLClientInterface::class, public: true)]
final class DeepLAPIClient extends OfficialDeepLClient implements DeepLClientInterface
{
    /**
     * @param array<TranslatorOptions::*|string, mixed> $options
     */
    public function __construct(
        #[Autowire(expression: 'service("deepl-core.configuration").getApiKey()')]
        string $apiKey,
        #[Autowire(expression: 'service("deepl-core.configuration").getConfigurationForDeepLClient()')]
        array $options = []
    ) {
        parent::__construct($apiKey, $options);
    }
}
