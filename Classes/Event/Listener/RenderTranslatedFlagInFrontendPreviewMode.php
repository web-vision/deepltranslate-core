<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Event\Listener;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;

/**
 * Event listener to render the frontend preview flag information.
 *
 * @internal for `deepltranslate-core` internal usage and not part of public API.
 */
final class RenderTranslatedFlagInFrontendPreviewMode
{
    public function __invoke(AfterCacheableContentIsGeneratedEvent $event): void
    {
        $controller = $this->getTypoScriptFrontendController($event);
        $context = $controller->getContext();
        if (
            !$this->isInPreviewMode($context)
            || $this->processWorkspacePreview($context)
            || ($controller->config['config']['disablePreviewNotification'] ?? false)
            || (
                isset($controller->page['tx_wvdeepltranslate_translated_time'])
                && $controller->page['tx_wvdeepltranslate_translated_time'] === 0
            )
        ) {
            // Preview flag must not be inserted. Return early.
            return;
        }

        $messagePreviewLabel = ($controller->config['config']['deepl_message_preview'] ?? '')
            ?: 'Translated with DeepL';

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
        $message = '<div id="deepl-preview-info" style="' . implode(';', $styles) . '">' . htmlspecialchars($messagePreviewLabel) . '</div>';

        $controller->content = str_ireplace('</body>', $message . '</body>', $controller->content);
    }

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

    private function getTypoScriptFrontendController(AfterCacheableContentIsGeneratedEvent $event): TypoScriptFrontendController
    {
        return $event->getController();
    }
}
