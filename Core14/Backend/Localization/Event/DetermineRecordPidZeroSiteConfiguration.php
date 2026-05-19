<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Core14\Backend\Localization\Event;

use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Fired for PID=0 records (not pages) in
 * {@see DeeplTranslateLocalizationHandler::determineSiteConfigAndSiteLanguageForLocalizationInstructions()}
 * to allow determination of usable site configuration.
 *
 * **Be aware** that this **must** be determininstic, which means source and target language must be for the
 * same configuration and targetLanguageId only resolves to a single site configuration. Otherwise site config
 * is not deterministic retrievable and using correct DeepL key is not guaranteed.
 *
 * @internal and not part of public API.
 */
final class DetermineRecordPidZeroSiteConfiguration
{
    /**
     * @param string $mainRecordType
     * @param array<string, mixed> $record
     * @param int $sourceLanguageId
     * @param int $targetLanguageId
     * @param Site|null $site
     */
    public function __construct(
        public readonly string $mainRecordType,
        public readonly array $record,
        public readonly int $sourceLanguageId,
        public readonly int $targetLanguageId,
        public ?Site $site,
    ) {}

    /**
     * @return array{site: Site|null, sourceLanguage: SiteLanguage|null, targetLanguage: SiteLanguage|null}
     */
    public function getResult(): array
    {
        if ($this->site === null) {
            return [
                'site' => null,
                'sourceLanguage' => null,
                'targetLanguage' => null,
            ];
        }
        $sourceSiteLanguage = $this->getValidSiteLanguage($this->site, $this->sourceLanguageId);
        $targetSiteLanguage = $this->getValidSiteLanguage($this->site, $this->targetLanguageId);
        if ($sourceSiteLanguage === null || $targetSiteLanguage === null) {
            return [
                'site' => null,
                'sourceLanguage' => null,
                'targetLanguage' => null,
            ];
        }
        return [
            'site' => $this->site,
            'sourceLanguage' => $sourceSiteLanguage,
            'targetLanguage' => $targetSiteLanguage,
        ];
    }

    private function getValidSiteLanguage(Site $site, int $languageId): ?SiteLanguage
    {
        try {
            return $site->getLanguageById($languageId);
        } catch (\Throwable) {
            return null;
        }
    }
}
