<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core;

use DeepL\DeepLException;
use DeepL\Usage as DeepLUsage;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use WebVision\Deepltranslate\Core\Client\DeepLClientInterface;

/**
 * Implementation for Usage statistics fetch against the DeepL API
 */
#[AsAlias(id: UsageInterface::class, public: true)]
final class Usage extends AbstractClient implements UsageInterface
{
    public function __construct(
        protected LoggerInterface $logger,
        protected DeepLClientInterface $client,
    ) {}

    public function getUsage(): ?DeepLUsage
    {
        try {
            return $this->client->getUsage();
        } catch (DeepLException $exception) {
            $this->logger->error(sprintf(
                '%s (%d)',
                $exception->getMessage(),
                $exception->getCode()
            ));
        }

        return null;
    }
}
