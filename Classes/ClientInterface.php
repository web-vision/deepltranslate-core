<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core;

use Psr\Log\LoggerInterface;
use WebVision\Deepltranslate\Core\Client\DeepLClientFactoryInterface;
use WebVision\Deepltranslate\Core\Client\DeepLClientInterface;

/**
 * Interface for custom client implementation and which methods are expected.
 *
 * @internal use only for testing, not part of public extension API.
 * @property DeepLClientInterface $clientFactory
 */
interface ClientInterface
{
    // @todo Interfaces should not define class constructor. This prevents class composition in case two interfaces
    //       declares constructors in the interface.
    public function __construct(
        LoggerInterface $logger,
        DeepLClientFactoryInterface $clientFactory,
    );
}
