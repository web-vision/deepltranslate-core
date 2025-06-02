<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core;

use DeepL\Translator;
use DeepL\TranslatorOptions;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Exception\ApiKeyNotSetException;

/**
 * @internal No public usage
 */
abstract class AbstractClient implements ClientInterface
{
    protected ConfigurationInterface $configuration;

    protected ?Translator $translator = null;

    protected LoggerInterface $logger;

    /**
     * Copied from TranslatorOptions
     * for iterating over Client Configuration
     * @see TranslatorOptions::OPTIONS_KEYS
     */
    private const OPTIONS_KEYS = [
        TranslatorOptions::SERVER_URL,
        TranslatorOptions::HEADERS,
        TranslatorOptions::TIMEOUT,
        TranslatorOptions::MAX_RETRIES,
        TranslatorOptions::PROXY,
        TranslatorOptions::LOGGER,
        TranslatorOptions::HTTP_CLIENT,
        TranslatorOptions::SEND_PLATFORM_INFO,
        TranslatorOptions::APP_INFO,
    ];

    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Wrapper function to handel ApiKey exception
     *
     * @throws ApiKeyNotSetException
     */
    protected function getTranslator(): Translator
    {
        if ($this->translator instanceof Translator) {
            return $this->translator;
        }
        if ($this->configuration->getApiKey() === '') {
            throw new ApiKeyNotSetException('The api key ist not set', 1708081233823);
        }
        $options[TranslatorOptions::HTTP_CLIENT] = GeneralUtility::makeInstance(GuzzleClientFactory::class)->getClient();
        foreach (self::OPTIONS_KEYS as $option) {
            if ($options[TranslatorOptions::HTTP_CLIENT]->getConfig($option) !== null) {
                $options[$option] = $options[TranslatorOptions::HTTP_CLIENT]->getConfig($option);
            }
        }
        $this->translator = new Translator($this->configuration->getApiKey(), $options);
        return $this->translator;
    }

}
