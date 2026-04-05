<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;
use WebVision\Deepltranslate\Core\Core13\Service\RenderTranslatedFlagInInFrontendPreviewModeService as Core13RenderTranslatedFlagInInFrontendPreviewModeService;
use WebVision\Deepltranslate\Core\Core14\Service\RenderTranslatedFlagInInFrontendPreviewModeService as Core14RenderTranslatedFlagInInFrontendPreviewModeService;

/**
 * {@see Core13RenderTranslatedFlagInInFrontendPreviewModeService}
 * {@see Core14RenderTranslatedFlagInInFrontendPreviewModeService}
 */
interface RenderTranslatedFlagInFrontendPreviewModeServiceInterface
{
    public function handleEvent(AfterCacheableContentIsGeneratedEvent $event): void;
}
