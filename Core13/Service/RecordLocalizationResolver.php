<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Core13\Service;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use WebVision\Deepltranslate\Core\Service\RecordLocalizationResolverInterface;

/**
 * TYPO3 v13 implementation of {@see RecordLocalizationResolverInterface}
 *
 * @todo Remove together with `Core13/*` when TYPO3 v13 support is dropped.
 */
#[AsAlias(id: RecordLocalizationResolverInterface::class)]
#[Autoconfigure(public: true)]
final class RecordLocalizationResolver implements RecordLocalizationResolverInterface
{
    public function hasTranslation(string $table, int $uid, int $languageId): bool
    {
        // Note: a translation with an empty `l10n_source` is not found by this lookup, because it
        // matches `ctrl.translationSource` and only falls back to `ctrl.transOrigPointerField` for
        // tables without a translation source field. Such records are created by TYPO3 itself.
        // Fixed in TYPO3 with https://forge.typo3.org/issues/110281
        // (13.4: https://review.typo3.org/c/Packages/TYPO3.CMS/+/94915), still unreleased.
        $localizedRecords = BackendUtility::getRecordLocalization($table, $uid, $languageId);

        return is_array($localizedRecords) && $localizedRecords !== [];
    }
}
