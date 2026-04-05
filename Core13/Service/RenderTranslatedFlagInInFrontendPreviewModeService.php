<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Core13\Service;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;
use WebVision\Deepltranslate\Core\Service\RenderTranslatedFlagInFrontendPreviewModeServiceInterface;
use WebVision\Deepltranslate\Core\Service\Trait\RenderTranslatedFlagInInFrontendPreviewModeServiceMethodsTrait;

/**
 * TYPO3 v13 implementation of {@see RenderTranslatedFlagInFrontendPreviewModeServiceInterface}
 */
#[AsAlias(id: RenderTranslatedFlagInFrontendPreviewModeServiceInterface::class)]
final class RenderTranslatedFlagInInFrontendPreviewModeService implements RenderTranslatedFlagInFrontendPreviewModeServiceInterface
{
    use RenderTranslatedFlagInInFrontendPreviewModeServiceMethodsTrait;

    public function handleEvent(AfterCacheableContentIsGeneratedEvent $event): void
    {
        if (!$this->shouldProcessEvent($event)) {
            return;
        }
        $this->injectMessage($event, $this->getMessage($event));
    }

    private function shouldProcessEvent(AfterCacheableContentIsGeneratedEvent $event): bool
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $disablePreviewNotification = $this->disablePreviewNotification($event);
        $deeplTranslateTranslatedTime = $this->getDeeplTranslateTranslatedTime($event);
        return (
            !$this->isInPreviewMode($context)
            || $this->processWorkspacePreview($context)
            || $disablePreviewNotification
            || $deeplTranslateTranslatedTime === 0
        ) === false;
    }

    private function injectMessage(AfterCacheableContentIsGeneratedEvent $event, string $message): void
    {
        if (trim($message) === '') {
            // Nothing to do.
            return;
        }
        $controller = $event->getController();
        $controller->content = str_ireplace('</body>', $message . '</body>', $controller->content);
    }

    private function getMessage(AfterCacheableContentIsGeneratedEvent $event): string
    {
        return sprintf(
            '<div id="deepl-preview-info" style="%s">%s</div>',
            $this->getStyles(),
            htmlspecialchars($this->getMessagePreviewLabel($event)),
        );
    }

    private function getMessagePreviewLabel(AfterCacheableContentIsGeneratedEvent $event): string
    {
        return ($this->getTypoScriptConfig($event)['deepl_message_preview'] ?? '')
            ?: 'Translated with DeepL';
    }

    private function getDeeplTranslateTranslatedTime(AfterCacheableContentIsGeneratedEvent $event): int
    {
        $pageRecord = $this->getPageRecord($event);
        return match (true) {
            isset($pageRecord['tx_wvdeepltranslate_translated_time']) => (int)$pageRecord['tx_wvdeepltranslate_translated_time'],
            default => 0,
        };
    }

    private function disablePreviewNotification(AfterCacheableContentIsGeneratedEvent $event): bool
    {
        return (bool)($this->getTypoScriptConfig($event)['disablePreviewNotification'] ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    private function getPageRecord(AfterCacheableContentIsGeneratedEvent $event): array
    {
        return $event->getController()->page ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function getTypoScriptConfig(AfterCacheableContentIsGeneratedEvent $event): array
    {
        return $event->getController()->config['config'] ?? [];
    }
}
