<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use DeepL\Usage;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use WebVision\Deepltranslate\Core\ClientInterface;
use WebVision\Deepltranslate\Core\Event\Listener\UsageToolBarEventListener;
use WebVision\Deepltranslate\Core\Hooks\UsageProcessAfterFinishHook;

/**
 * Service for getting the current count and limit of DeepL API
 */
#[Autoconfigure(public: true)]
final class UsageService implements UsageServiceInterface
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly Locales $locales
    ) {
    }

    public function getCurrentUsage(): ?Usage
    {
        return $this->client->getUsage();
    }

    public function checkTranslateLimitWillBeExceeded(string $contentToTranslate): bool
    {
        $usage = $this->getCurrentUsage();
        if ($usage === null) {
            return false;
        }
        if ($usage->character === null) {
            return true;
        }
        $currentCount = $usage->character->count;
        $toTranslateCount = strlen(strip_tags($contentToTranslate));
        return ($currentCount + $toTranslateCount) > $usage->character->limit;
    }

    /**
     * @inheritDoc
     */
    public function isTranslateLimitExceeded(): bool
    {
        $usage = $this->getCurrentUsage();
        if ($usage === null || $usage->character === null) {
            return false;
        }
        return $usage->character->count >= $usage->character->limit;
    }

    /**
     * Make large API limits easier to read
     *
     * @param int $number Any large integer - 5000000
     * @return string|false Formated, better readable string variant of the integer - 5.000.000
     */
    public function formatNumber(int $number)
    {
        $language = 'default';
        if ($this->getBackendUser() !== null) {
            $uc = $this->getBackendUser()->uc;
            if (is_array($uc) && array_key_exists('lang', $uc)) {
                $language = $uc['lang'];
            }
        }

        $locale = $this->locales->createLocale($language);
        $numberFormatter = new \NumberFormatter($locale->getLanguageCode(), \NumberFormatter::DECIMAL);
        return $numberFormatter->format($number);
    }

    /**
     * Calculate the message severity based on the quota usage rate
     *
     * Only to be used in {@see UsageProcessAfterFinishHook::processCmdmap_afterFinish()}.
     *
     * @param int $characterCount Already translated characters in the current billing period
     * @param int $characterLimit Total character limit in the current billing period
     * @return ContextualFeedbackSeverity Severity level
     *
     * @internal to be used only within `web-vision/deepltranslate-core`, not part of public API.
     */
    public function determineSeverity(int $characterCount, int $characterLimit): ContextualFeedbackSeverity
    {
        $quotaUtilization = ($characterCount / $characterLimit) * 100;
        if ($quotaUtilization >= 100) {
            return ContextualFeedbackSeverity::ERROR;
        }
        if ($quotaUtilization >= 98) {
            return ContextualFeedbackSeverity::WARNING;
        }
        if ($quotaUtilization >= 90) {
            return ContextualFeedbackSeverity::INFO;
        }
        return ContextualFeedbackSeverity::NOTICE;
    }

    /**
     * Used only in {@see UsageToolBarEventListener::__invoke()}.
     *
     * @internal to be used only within `web-vision/deepltranslate-core`, not part of public API.
     */
    public function determineSeverityForSystemInformation(int $characterCount, int $characterLimit): string
    {
        $quotaUtilization = ($characterCount / $characterLimit) * 100;
        if ($quotaUtilization >= 100) {
            return InformationStatus::STATUS_ERROR;
        }
        if ($quotaUtilization >= 98) {
            return InformationStatus::STATUS_WARNING;
        }
        if ($quotaUtilization >= 90) {
            return InformationStatus::STATUS_INFO;
        }
        return InformationStatus::STATUS_NOTICE;
    }

    private function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
