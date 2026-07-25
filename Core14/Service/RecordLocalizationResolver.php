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
