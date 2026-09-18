<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Core14\Backend\Localization;

use TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository;
use TYPO3\CMS\Backend\Localization\Finisher\LocalizationFinisherInterface;
use TYPO3\CMS\Backend\Localization\Finisher\RedirectLocalizationFinisher;
use TYPO3\CMS\Backend\Localization\Finisher\ReloadLocalizationFinisher;
use TYPO3\CMS\Backend\Localization\LocalizationResult;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use WebVision\Deepltranslate\Core\Domain\Dto\InlineParentReference;
use WebVision\Deepltranslate\Core\Service\InlineRelationResolver;
use WebVision\Deepltranslate\Core\Service\RecordLocalizationResolverInterface;

/**
 * The parts of the TYPO3 v14 localization wizard handlers shared for an inline (IRRE) child in connected
 * mode, which is localized through `inlineLocalizeSynchronize` of its translated parent.
 *
 * Used by {@see DeeplTranslateLocalizationHandler} and {@see InlineChildAwareManualLocalizationHandler}.
 *
 * @internal No public API
 */
final readonly class InlineChildLocalization
{
    /**
     * Nested inline records are followed up to this depth to find the record the edit form is opened for.
     */
    private const MAX_NESTING_DEPTH = 10;

    public function __construct(
        private InlineRelationResolver $inlineRelationResolver,
        private RecordLocalizationResolverInterface $recordLocalizationResolver,
        private LocalizationRepository $localizationRepository,
        private UriBuilder $uriBuilder,
    ) {}

    public function hasTranslatedParent(InlineParentReference $reference, int $targetLanguageId): bool
    {
        return $this->recordLocalizationResolver->hasTranslation($reference->parentTable, $reference->parentUid, $targetLanguageId);
    }

    public function createParentNotTranslatedResult(): LocalizationResult
    {
        return LocalizationResult::error([
            $this->getLanguageService()->sL('deepltranslate_core.wizards.localization:error.inlineParentNotTranslated'),
        ]);
    }

    /**
     * An empty error log of the DataHandler does not prove that the child has been localized:
     * `inlineLocalizeSynchronize` returns silently, for example when the only record of the parent in the
     * target language is a free mode copy. So the result is checked in the database.
     *
     * @param array<array-key, mixed> $errorLog
     */
    public function createResult(InlineParentReference $reference, int $targetLanguageId, array $errorLog): LocalizationResult
    {
        if ($errorLog !== []) {
            return LocalizationResult::error($errorLog);
        }
        if (!$this->recordLocalizationResolver->hasTranslation($reference->childTable, $reference->childUid, $targetLanguageId)) {
            return LocalizationResult::error([
                $this->getLanguageService()->sL('deepltranslate_core.wizards.localization:error.inlineChildNotLocalized'),
            ]);
        }

        return LocalizationResult::success($this->createFinisher($reference, $targetLanguageId));
    }

    /**
     * The edit form of the translated record the child belongs to, the top-most one for nested inline
     * records, which is where the editor usually starts the wizard from.
     *
     * A redirect and not a reload: the redirect goes through the content container of the backend, which
     * asks before unsaved changes of an open edit form are discarded. The reload finisher reloads the whole
     * backend when the wizard is shown in the top window and discards them without asking.
     */
    private function createFinisher(InlineParentReference $reference, int $targetLanguageId): LocalizationFinisherInterface
    {
        $table = $reference->parentTable;
        $uid = $reference->parentUid;
        for ($depth = 0; $depth < self::MAX_NESTING_DEPTH; $depth++) {
            $parentReference = $this->inlineRelationResolver->resolveParentReference($table, $uid)->reference;
            if ($parentReference === null) {
                break;
            }
            $table = $parentReference->parentTable;
            $uid = $parentReference->parentUid;
        }
        $translation = $this->localizationRepository->getRecordTranslation(
            $table,
            $uid,
            $targetLanguageId,
            $this->getBackendUser()->workspace,
        );
        if ($translation === null) {
            return new ReloadLocalizationFinisher();
        }

        return new RedirectLocalizationFinisher((string)$this->uriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [
                $table => [
                    $translation->getUid() => 'edit',
                ],
            ],
            'returnUrl' => (string)$this->uriBuilder->buildUriFromRoute('web_layout', [
                // The page module is opened with the default language page, the page itself for a page.
                'id' => $table === 'pages' ? $uid : $translation->getPid(),
                'languages' => [$targetLanguageId],
            ]),
        ]));
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
