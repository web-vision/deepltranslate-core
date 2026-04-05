<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Event\Listener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;
use WebVision\Deepltranslate\Core\Service\RenderTranslatedFlagInFrontendPreviewModeServiceInterface;

/**
 * Event listener to render the frontend preview flag information.
 *
 * @internal for `deepltranslate-core` internal usage and not part of public API.
 */
final readonly class RenderTranslatedFlagInFrontendPreviewMode
{
    public function __construct(
        private RenderTranslatedFlagInFrontendPreviewModeServiceInterface $renderTranslatedFlagInFrontendPreviewModeService,
    ) {}

    #[AsEventListener(
        identifier: 'deepltranslate-core/render-translated-flag-in-frontend-preview-mode',
    )]
    public function __invoke(AfterCacheableContentIsGeneratedEvent $event): void
    {
        $this->renderTranslatedFlagInFrontendPreviewModeService->handleEvent($event);
    }
}
