<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Client;

use DeepL\DeepLClient;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use WebVision\Deepltranslate\Core\Client\DeepLClientInterface;

/**
 * Wrapper class for the DeepL PHP API.
 */
#[AsAlias(id: DeepLClientInterface::class)]
final class DeepLAPIClient extends DeepLClient implements DeepLClientInterface
{
}
