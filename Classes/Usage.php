<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core;

use DeepL\DeepLException;
use DeepL\Usage as DeepLUsage;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use WebVision\Deepltranslate\Core\Client\DeepLClientFactoryInterface;

/**
 * Default implementation of {@see UsageInterface} to provide Usage statistics fetch using the DeepL API.
 * @internal and not part of public API.
 */
#[AsAlias(id: UsageInterface::class, public: true)]
final class Usage extends AbstractClient implements UsageInterface
{
    /**
     * @internal
     * @todo typo3/cms-core:>=13.4.29 Replace constructor with `inject*()` methods in {@see AbstractClient},
     *       link: https://review.typo3.org/c/Packages/TYPO3.CMS/+/89244
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected DeepLClientFactoryInterface $clientFactory,
    ) {}

    public function getUsage(): ?DeepLUsage
    {
        try {
            return $this->client()->getUsage();
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
