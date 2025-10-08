<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Client;

use DeepL\GlossaryEntries;
use DeepL\GlossaryInfo;
use DeepL\TextResult;
use Psr\Log\LoggerInterface;
use WebVision\Deepltranslate\Core\TranslatorInterface;

class DummyTranslateClient implements TranslatorInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getGlossaryLanguagePairs(): array
    {
        $this->logger->info('Dummy DeepL called');
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAllGlossaries(): array
    {
        $this->logger->info('Dummy DeepL called');
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getGlossary(string $glossaryId): ?GlossaryInfo
    {
        $this->logger->info('Dummy DeepL called');
        return null;
    }

    /**
     * @inheritDoc
     */
    public function createGlossary(string $glossaryName, string $sourceLang, string $targetLang, array $entries): GlossaryInfo
    {
        $this->logger->info('Dummy DeepL called');
        return new GlossaryInfo('', '', false, $sourceLang, $targetLang, new \DateTime(), count($entries));
    }

    /**
     * @inheritDoc
     */
    public function deleteGlossary(string $glossaryId): void
    {
        $this->logger->info('Dummy DeepL called');
    }

    /**
     * @inheritDoc
     */
    public function getGlossaryEntries(string $glossaryId): ?GlossaryEntries
    {
        $this->logger->info('Dummy DeepL called');
        return null;
    }

    /**
     * @inheritDoc
     */
    public function translate(string $content, ?string $sourceLang, string $targetLang, string $glossary = '', string $formality = ''): array|TextResult|null
    {
        $this->logger->info('Dummy DeepL called');
        return new TextResult($content, $sourceLang, 0);
    }

    /**
     * @inheritDoc
     */
    public function getSupportedLanguageByType(string $type = 'target'): array
    {
        $this->logger->info('Dummy DeepL called');
        return [];
    }
}
