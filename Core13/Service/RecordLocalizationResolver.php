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
        $localizedRecords = BackendUtility::getRecordLocalization($table, $uid, $languageId);

        return is_array($localizedRecords) && $localizedRecords !== [];
    }
}
