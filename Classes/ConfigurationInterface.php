<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core;

/**
 * Describes required configuration value retrievement methods which are essential.
 *
 * @internal usage only and not meant for extending. **Should** still be considered as public and changes should
 *           respect general deprecation policy rules as it may be accessed by consumers.
 */
interface ConfigurationInterface
{
    public function getApiKey(): string;

    public function getModelType(): string;

    public function isSplitSentenceEnabled(): bool;
    public function isPreserveFormattingEnabled(): bool;

    public function getIgnoreTags(): string;

    public function getNonSplittingTags(): string;

    public function getSplittingTags(): string;

    public function isOutlineDetectionEnabled(): bool;
}
