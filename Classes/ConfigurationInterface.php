<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core;

use DeepL\GlossaryInfo;
use DeepL\MultilingualGlossaryInfo;
use DeepL\StyleRuleInfo;

/**
 * Describes required configuration value retrieve methods which are essential.
 *
 * @internal usage only and not meant for extending. **Should** still be considered as public and changes should
 *           respect general deprecation policy rules as it may be accessed by consumers.
 */
interface ConfigurationInterface
{
    public function getApiKey(): string;
    public function getModelType(): string;
    public function getSplitSentences(): string;
    public function isPreserveFormattingEnabled(): bool;
    public function getIgnoreTags(): string;
    public function getNonSplittingTags(): string;
    public function getSplittingTags(): string;
    public function isOutlineDetectionEnabled(): bool;

    /**
     * @return array{}
     */
    public function getConfigurationForDeepLClient(): array;

    /**
     * @return array{
     *      split_sentences?: 'on'|'off'|'default'|'nonewlines',
     *      preserve_formatting?: bool,
     *      formality?: 'default'|'more'|'less'|'prefer_more'|'prefer_less',
     *      context?: string,
     *      tag_handling?: 'xml'|'html',
     *      tag_handling_version?: 'v1'|'v2',
     *      outline_detection?: bool,
     *      splitting_tags?: string[],
     *      non_splitting_tags?: string[],
     *      ignore_tags?: string[],
     *      glossary?: string|GlossaryInfo|MultilingualGlossaryInfo,
     *      model_type?: 'quality_optimized'|'prefer_quality_optimized'|'latency_optimized',
     *      extra_body_parameters?: array<string, string>,
     *      style_id?: string|StyleRuleInfo,
     *      custom_instructions?: string[]
     *  }
     */
    public function getConfigurationForTranslation(): array;
}
