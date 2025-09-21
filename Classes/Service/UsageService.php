<?php

declare(strict_types=1);

namespace WebVision\WvDeepltranslate\Service;

use DeepL\Usage;
use TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use WebVision\WvDeepltranslate\ClientInterface;

final class UsageService implements UsageServiceInterface
{
    protected ClientInterface $client;

    public function __construct(
        ClientInterface $client
    ) {
        $this->client = $client;
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
        // @todo typo3/cms-core:>=12 remove polyfill switch, as php-intl is
        //       then a hard requirement and the polyfill is not needed
        if ((new Typo3Version())->getMajorVersion() <= 12 && !extension_loaded('intl')) {
            if (!class_exists(\NumberFormatter::class)) {
                return number_format($number);
            }
            // TYPO3 v11 has a Symfony Polyfill, but this ony allows
            // locale en or null, so we call with default parameter.
            $numberFormatter = new \NumberFormatter('en', \NumberFormatter::DECIMAL);
        } else {
            $language = 'default';
            if ($this->getBackendUser() !== null) {
                $uc = $this->getBackendUser()->uc;
                if (is_array($uc) && array_key_exists('lang', $uc)) {
                    $language = $uc['lang'];
                }
            }
            $locale = ($language !== 'default') ? $language : 'en';
            $numberFormatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
        }
        return $numberFormatter->format($number);
    }

    /**
     * Calculate the message severity based on the quota usage rate
     *
     * @param int $characterCount Already translated characters in the current billing period
     * @param int $characterLimit Total character limit in the current billing period
     * @return int Severity level
     */
    public function determineSeverity(int $characterCount, int $characterLimit): int
    {
        $quotaUtilization = ($characterCount / $characterLimit) * 100;
        if ($quotaUtilization >= 100) {
            return FlashMessage::ERROR;
        }
        if ($quotaUtilization >= 98) {
            return FlashMessage::WARNING;
        }
        if ($quotaUtilization >= 90) {
            return FlashMessage::INFO;
        }
        return FlashMessage::NOTICE;
    }

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
