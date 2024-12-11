<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Hooks;

use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class DeeplPreviewFlagGeneratePageHook
{
    /**
     * @param array{pObj: TypoScriptFrontendController} $params
     * @throws AspectNotFoundException
     */
    public function renderDeeplPreviewFlag(array $params): void
    {
        $controller = $params['pObj'];

        $isInPreviewMode = $controller->getContext()->hasAspect('frontend.preview')
            && $controller->getContext()->getPropertyFromAspect('frontend.preview', 'isPreview');
        if (
            !$isInPreviewMode
            || $controller->getContext()->getPropertyFromAspect('workspace', 'isOffline', false)
            || ($controller->config['config']['disablePreviewNotification'] ?? false)
            || (
                isset($controller->page['tx_wvdeepltranslate_translated_time'])
                && $controller->page['tx_wvdeepltranslate_translated_time'] === 0
            )
        ) {
            return;
        }

        $messagePreviewLabel = $controller->config['config']['deepl_message_preview'] ?? '';
        if ($messagePreviewLabel === '') {
            $messagePreviewLabel = 'Translated with DeepL';
        }

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
}
