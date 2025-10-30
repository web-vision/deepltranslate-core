<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Widgets;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use WebVision\Deepltranslate\Core\Service\UsageService;

/**
 * `EXT:dashboard` widget compatible with TYPO3 v12 to display deepl api usage.
 *
 * Cannot be autoconfigured, as this class depends on EXT:dashboard, which is not installed in every system.
 * Registration and configuring is done in Configuration/Services.php
 *
 * @internal implementation only and not part of public API.
 */
final class UsageWidget implements RequestAwareWidgetInterface, WidgetInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $options;

    private ServerRequestInterface $request;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly BackendViewFactory $backendViewFactory,
        private readonly UsageService $usageService,
        array $options = []
    ) {
        $this->options = $options;
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $currentUsage = $this->usageService->getCurrentUsage();
        $view = $this->backendViewFactory->create($this->request, ['typo3/cms-dashboard', 'web-vision/deepltranslate-core']);
        $view->assignMultiple([
            'usages' => [
                [
                    'label' => $this->getLanguageService()->sL('LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:widgets.deepltranslate.widget.useswidget.character'),
                    'usage' => $currentUsage !== null ? $currentUsage->character : [],
                ],
            ],
            'options' => $this->options,
            'configuration' => $this->configuration,
        ]);

        return $view->render('Widget/UsageWidget');
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
