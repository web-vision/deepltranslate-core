<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Core14\Service;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use WebVision\Deepltranslate\Core\Service\RecordLocalizationResolverInterface;

/**
 * TYPO3 v14 implementation of {@see RecordLocalizationResolverInterface}
 */
#[AsAlias(id: RecordLocalizationResolverInterface::class)]
#[Autoconfigure(public: true)]
final class RecordLocalizationResolver implements RecordLocalizationResolverInterface
{
    public function __construct(
        private readonly LocalizationRepository $localizationRepository,
    ) {}

    public function hasTranslation(string $table, int $uid, int $languageId): bool
    {
        // Note: a translation with an empty `l10n_source` is not found by this lookup, because it
        // matches `ctrl.translationSource` and only falls back to `ctrl.transOrigPointerField` for
        // tables without a translation source field. Such records are created by TYPO3 itself.
        // Fixed in TYPO3 with https://forge.typo3.org/issues/110281
        // (14.3: https://review.typo3.org/c/Packages/TYPO3.CMS/+/94916), still unreleased.
        return $this->localizationRepository->getRecordTranslation(
            $table,
            $uid,
            $languageId,
            $this->getBackendUser()->workspace,
        ) !== null;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
