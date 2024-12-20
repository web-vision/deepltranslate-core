<?php

declare(strict_types=1);

namespace WebVision\WvDeepltranslate\Domain\Dto;

/**
 * @internal
 */
final class TranslateOptions
{
    protected string $splitSentences = 'nonewlines';

    protected bool $outlineDetection = false;

    protected array $splittingTags = [];

    protected array $nonSplittingTags = [];

    protected array $ignoreTags = [];

    protected string $tagHandling = 'xml';

    protected string $targetLanguage;

    protected string $sourceLanguage;

    public function getSplitSentences(): string
    {
        return $this->splitSentences;
    }

    public function setSplitSentences(string $splitSentences): void
    {
        $this->splitSentences = $splitSentences;
    }

    public function isOutlineDetection(): bool
    {
        return $this->outlineDetection;
    }

    public function setOutlineDetection(bool $outlineDetection): void
    {
        $this->outlineDetection = $outlineDetection;
    }

    public function getSplittingTags(): array
    {
        return $this->splittingTags;
    }

    public function setSplittingTags(array $splittingTags): void
    {
        $this->splittingTags = $splittingTags;
    }

    public function getNonSplittingTags(): array
    {
        return $this->nonSplittingTags;
    }

    public function setNonSplittingTags(array $nonSplittingTags): void
    {
        $this->nonSplittingTags = $nonSplittingTags;
    }

    public function getIgnoreTags(): array
    {
        return $this->ignoreTags;
    }

    public function setIgnoreTags(array $ignoreTags): void
    {
        $this->ignoreTags = $ignoreTags;
    }

    public function getTagHandling(): string
    {
        return $this->tagHandling;
    }

    public function setTagHandling(string $tagHandling): void
    {
        $this->tagHandling = $tagHandling;
    }

    public function getTargetLanguage(): string
    {
        return $this->targetLanguage;
    }

    public function setTargetLanguage(string $targetLanguage): void
    {
        $this->targetLanguage = $targetLanguage;
    }

    public function getSourceLanguage(): string
    {
        return $this->sourceLanguage;
    }

    public function setSourceLanguage(string $sourceLanguage): void
    {
        $this->sourceLanguage = $sourceLanguage;
    }

    public function toArray(): array
    {
        $param = [];
        $param['tag_handling'] = $this->tagHandling;

        if (!empty($this->splittingTags)) {
            $param['outlineDetection'] = $this->outlineDetection;
            $param['split_sentences'] = $this->splitSentences;
            $param['splitting_tags'] = implode(',', $this->splittingTags);
        }

        $param['source_lang'] = $this->sourceLanguage;
        $param['target_lang'] = $this->targetLanguage;

        return $param;
    }
}
