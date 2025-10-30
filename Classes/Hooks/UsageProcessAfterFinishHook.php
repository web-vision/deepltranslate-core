<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Hooks;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Service\UsageService;

/**
 * This hook adds a FlashMessage, if the usage quota is reaching a critical level, so editors can take care
 * on and ensure to either increase their quota in DeepL or stop translating to keep the hard limit untouched.
 */
#[Autoconfigure(public: true)]
final class UsageProcessAfterFinishHook
{
    private UsageService $usageService;

    public function __construct(
        UsageService $usageService
    ) {
        $this->usageService = $usageService;
    }

    public function processCmdmap_afterFinish(DataHandler $dataHandler): void
    {
        if (!isset($dataHandler->cmdmap['deepltranslate'])) {
            return;
        }

        if (Environment::isCli() || Environment::getContext()->isTesting()) {
            return;
        }

        $usage = $this->usageService->getCurrentUsage();
        if ($usage === null || $usage->character === null) {
            return;
        }

        $title = $this->getLanguageService()->sL(
            'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:usages.flashmassage.title'
        );
        $message = $this->getLanguageService()->sL(
            'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:usages.flashmassage.limit.description'
        );

        $severity = $this->usageService->determineSeverity($usage->character->count, $usage->character->limit);
        // Reduce noise - Don't bother editors with low quota usage messages
        if ($severity === ContextualFeedbackSeverity::NOTICE) {
            return;
        }

        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $notificationQueue = $flashMessageService->getMessageQueueByIdentifier();

        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            sprintf(
                $message,
                $this->usageService->formatNumber($usage->character->count) ?: $usage->character->count,
                $this->usageService->formatNumber($usage->character->limit)
            ) ?: $usage->character->limit,
            $title,
            $severity,
            true
        );

        $notificationQueue->addMessage($flashMessage);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
