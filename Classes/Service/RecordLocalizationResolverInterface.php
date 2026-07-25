<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use WebVision\Deepltranslate\Core\Core13\Service\RecordLocalizationResolver as Core13RecordLocalizationResolver;
use WebVision\Deepltranslate\Core\Core14\Service\RecordLocalizationResolver as Core14RecordLocalizationResolver;

/**
 * Core version independent lookup whether a record already has a translation for a target language.
 *
 * TYPO3 v13 and v14 provide different APIs for this: `BackendUtility::getRecordLocalization()` has
 * been deprecated in TYPO3 v14 in favour of `LocalizationRepository::getRecordTranslation()`, which
 * in turn does not exist in TYPO3 v13.
 *
 * {@see Core13RecordLocalizationResolver}
 * {@see Core14RecordLocalizationResolver}
 */
interface RecordLocalizationResolverInterface
{
    public function hasTranslation(string $table, int $uid, int $languageId): bool;
}
