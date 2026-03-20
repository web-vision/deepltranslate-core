<?php

namespace WebVision\Deepltranslate\Core;

use DeepL\DeepLException;
use DeepL\TextResult;
use DeepL\TranslateTextOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use WebVision\Deepltranslate\Core\Client\DeepLClientInterface;

#[AsAlias(id: TranslatorInterface::class, public: true)]
final class Translator extends AbstractClient implements TranslatorInterface
{
    public function __construct(
        protected LoggerInterface $logger,
        protected DeepLClientInterface $client
    ) {}

    /**
     * @return TextResult|TextResult[]|null
     */
    public function translate(
        string $content,
        ?string $sourceLang,
        string $targetLang,
        string $glossary = '',
        string $formality = ''
    ): array|null|TextResult {
        $options = [
            // @todo Make this configurable, either as global setting or dependency injection (factory?) / event
            TranslateTextOptions::FORMALITY => $formality ?: 'default',
            // @todo Make this configurable, either as global setting or dependency injection (factory?) / event
            TranslateTextOptions::TAG_HANDLING => 'html',
            // @todo Make this configurable, either as global setting or dependency injection (factory?) / event
            TranslateTextOptions::TAG_HANDLING_VERSION => 'v2',
        ];

        if (!empty($glossary)) {
            $options[TranslateTextOptions::GLOSSARY] = $glossary;
        }

        try {
            return $this->client->translateText(
                $content,
                $sourceLang,
                $targetLang,
                $options
            );
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
