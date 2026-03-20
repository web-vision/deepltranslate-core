<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core;

use Psr\Log\LoggerInterface;
use WebVision\Deepltranslate\Core\Client\DeepLClientInterface;

/**
 * Interface for custom client implementation and which methods are expected.
 *
 * @internal use only for testing, not part of public extension API.
 * @property DeepLClientInterface $client
 */
interface ClientInterface
{
    public function __construct(
        LoggerInterface $logger,
        DeepLClientInterface $client
    );
}
