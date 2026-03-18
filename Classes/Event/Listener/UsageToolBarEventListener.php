<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Event\Listener;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Backend\Event\SystemInformationToolbarCollectorEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Localization\LanguageService;
use WebVision\Deepltranslate\Core\Exception\ApiKeyNotSetException;
use WebVision\Deepltranslate\Core\Service\UsageService;

class UsageToolBarEventListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private UsageService $usageService;

    public function __construct(
        UsageService $usageService
    ) {
        $this->usageService = $usageService;
    }

    #[AsEventListener(
        identifier: 'deepltranslate-core/usages',
    )]
    public function __invoke(SystemInformationToolbarCollectorEvent $systemInformation): void
    {
        $character = null;
        try {
            $usage = $this->usageService->getCurrentUsage();
            // @todo Decide to handle empty UsageDetail later and add systeminformation with a default
            //       (no limit retrieved) instead of simply omitting it here now.
            if ($usage === null || $usage->character === null) {
                return;
            }
        } catch (ApiKeyNotSetException $exception) {
            // @todo Can be replaced with `$this->logger?->` when TYPO3 v11 and therefore PHP 7.4/8.0 support is dropped.
            if ($this->logger !== null) {
                $this->logger->error(sprintf('%s (%d)', $exception->getMessage(), $exception->getCode()));
            }
            return;
        }

        $title = $this->getLanguageService()->sL(
            'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:usages.toolbar-label'
        );
        $message = $this->getLanguageService()->sL(
            'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:usages.toolbar.message'
        );

        $severity = $this->usageService->determineSeverityForSystemInformation($usage->character->count, $usage->character->limit);

        $systemInformation->getToolbarItem()->addSystemInformation(
            $title,
            sprintf(
                $message,
                $this->usageService->formatNumber($usage->character->count),
                $this->usageService->formatNumber($usage->character->limit)
            ),
            'actions-localize-deepl',
            $severity
        );
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
