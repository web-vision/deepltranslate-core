<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Client;

use WebVision\Deepltranslate\Core\ConfigurationInterface;

final class DeepLClientFactory
{
    public function __construct(private readonly ConfigurationInterface $configuration)
    {
    }
}
