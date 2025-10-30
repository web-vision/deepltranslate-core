<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Hooks;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * Adds the labels from deepltranslate_core to the backend, so they are available, as the default core functionality
 * is not working
 */
#[Autoconfigure(public: true)]
final class PageRendererHook
{
    /**
     * Ensure backend javascript module is required and loaded.
     *
     * @param array<string, mixed> $params
     */
    public function renderPreProcess(array $params, PageRenderer $pageRenderer): void
    {
        if ($pageRenderer->getApplicationType() === 'BE') {
            // For some reason, the labels are not available in JavaScript object `TYPO3.lang`. So we add them manually.
            $pageRenderer->addInlineLanguageLabelFile('EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf');
        }
    }
}
