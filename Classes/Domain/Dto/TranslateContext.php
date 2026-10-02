<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Domain\Dto;

use Symfony\Component\DependencyInjection\Attribute\Exclude;
use WebVision\Deepltranslate\Core\Domain\Enum\ContentFormat;
use WebVision\Deepltranslate\Core\Service\LostLink;

/**
 * DTO providing the data shipped to DeepL translation.
 */
#[Exclude]
final class TranslateContext
{
    protected string $content = '';

    protected string $targetLanguageCode = '';

    protected string $sourceLanguageCode = '';

    protected string $formality = 'default';

    protected string $glossaryId = '';

    protected ContentFormat $contentFormat = ContentFormat::Unknown;

    /**
     * @var list<LostLink>
     */
    private array $lostLinks = [];

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getTargetLanguageCode(): string
    {
        return $this->targetLanguageCode;
    }

    public function setTargetLanguageCode(string $targetLanguageCode): void
    {
        $this->targetLanguageCode = $targetLanguageCode;
    }

    public function getSourceLanguageCode(): ?string
    {
        if ($this->sourceLanguageCode === 'auto') {
            return null;
        }

        return $this->sourceLanguageCode;
    }

    public function setSourceLanguageCode(string $sourceLanguageCode): void
    {
        $this->sourceLanguageCode = $sourceLanguageCode;
    }

    public function getFormality(): string
    {
        return $this->formality;
    }

    public function setFormality(string $formality): void
    {
        $this->formality = $formality;
    }

    public function getGlossaryId(): string
    {
        return $this->glossaryId;
    }

    public function setGlossaryId(string $glossaryId): void
    {
        $this->glossaryId = $glossaryId;
    }

    public function getContentFormat(): ContentFormat
    {
        return $this->contentFormat;
    }

    public function setContentFormat(ContentFormat $contentFormat): void
    {
        $this->contentFormat = $contentFormat;
    }

    /**
     * Links of the content the translation lost, set by
     * {@see \WebVision\Deepltranslate\Core\Service\DeeplService::translateContent()}, so the caller can tell the
     * editor which link to add again.
     *
     * @return list<LostLink>
     */
    public function getLostLinks(): array
    {
        return $this->lostLinks;
    }

    /**
     * @param list<LostLink> $lostLinks
     */
    public function setLostLinks(array $lostLinks): void
    {
        $this->lostLinks = $lostLinks;
    }
}
