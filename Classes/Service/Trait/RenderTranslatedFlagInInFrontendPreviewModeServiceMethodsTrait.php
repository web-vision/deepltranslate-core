<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service\Trait;

use TYPO3\CMS\Core\Context\Context;

trait RenderTranslatedFlagInInFrontendPreviewModeServiceMethodsTrait
{
    private function isInPreviewMode(Context $context): bool
    {
        return $context->hasAspect('frontend.preview')
            && $context->getPropertyFromAspect('frontend.preview', 'isPreview', false);
    }

    private function processWorkspacePreview(Context $context): bool
    {
        return $context->hasAspect('workspace')
            && $context->getPropertyFromAspect('workspace', 'isOffline', false);
    }

    private function getStyles(): string
    {
        $styles = [];
        $styles[] = 'position: fixed';
        $styles[] = 'top: 65px';
        $styles[] = 'right: 15px';
        $styles[] = 'padding: 8px 18px';
        $styles[] = 'background: #006494';
        $styles[] = 'border: 1px solid #006494';
        $styles[] = 'font-family: sans-serif';
        $styles[] = 'font-size: 14px';
        $styles[] = 'font-weight: bold';
        $styles[] = 'color: #fff';
        $styles[] = 'z-index: 20000';
        $styles[] = 'user-select: none';
        $styles[] = 'pointer-events: none';
        $styles[] = 'text-align: center';
        $styles[] = 'border-radius: 2px';
        return implode(';', $styles);
    }
}
