<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Event\Listener;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Backend\Backend\Event\SystemInformationToolbarCollectorEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use WebVision\Deepltranslate\Core\Exception\ApiKeyNotSetException;
use WebVision\Deepltranslate\Core\Service\UsageService;

/**
 * Listen to {@see SystemInformationToolbarCollectorEvent} to add usage information
 * to TYPO3 SystemInformation toolbar section providing an overview for the current
 * backend user.
 */
final readonly class UsageToolBarEventListener
{
    public function __construct(
        private UsageService $usageService,
        private LoggerInterface $logger,
    ) {}

    #[AsEventListener(
        identifier: 'deepltranslate-core/usages',
    )]
    public function __invoke(SystemInformationToolbarCollectorEvent $systemInformation): void
    {
        try {
            $usage = $this->usageService->getCurrentUsage();
            // @todo Consider to handle empty UsageDetail later and add SystemInformation with a default
            //       (no limit retrieved) instead of simply omitting it here now.
            if ($usage === null || $usage->character === null) {
                return;
            }
        } catch (ApiKeyNotSetException $exception) {
            $this->logger->error(sprintf('%s (%d)', $exception->getMessage(), $exception->getCode()));
            return;
        }
        $systemInformation->getToolbarItem()->addSystemInformation(
            $this->getLanguageService()->sL('LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:usages.toolbar-label'),
            sprintf(
                $this->getLanguageService()->sL('LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:usages.toolbar.message'),
                $this->usageService->formatNumber($usage->character->count),
                $this->usageService->formatNumber($usage->character->limit)
            ),
            sprintf('actions-localize-deepl-%s', ((new Typo3Version())->getMajorVersion())),
            $this->usageService->determineSeverityForSystemInformation($usage->character->count, $usage->character->limit),
        );
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
