<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core;

use DeepL\DeepLException;
use DeepL\Language;
use Psr\Log\LoggerInterface;
use WebVision\Deepltranslate\Core\Client\DeepLClientFactoryInterface;
use WebVision\Deepltranslate\Core\Client\DeepLClientInterface;

/**
 * @internal No public usage
 */
abstract class AbstractClient implements ClientInterface
{
    protected ?DeepLClientInterface $client;
    protected DeepLClientFactoryInterface $clientFactory;
    protected LoggerInterface $logger;

    /**
     * @return Language[]
     */
    public function getSupportedLanguageByType(string $type = 'target'): array
    {
        try {
            return ($type === 'target')
                ? $this->client()->getTargetLanguages()
                : $this->client()->getSourceLanguages();
        } catch (DeepLException $exception) {
            $this->logger->error(sprintf(
                '%s (%d)',
                $exception->getMessage(),
                $exception->getCode()
            ));
        }

        return [];
    }

    protected function client(): DeepLClientInterface
    {
        $this->client ??= $this->clientFactory->create($this);
        return $this->client;
    }
}
