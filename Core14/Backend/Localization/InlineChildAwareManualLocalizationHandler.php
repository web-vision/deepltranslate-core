<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Core14\Backend\Localization;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TYPO3\CMS\Backend\Localization\Finisher\NoopLocalizationFinisher;
use TYPO3\CMS\Backend\Localization\Finisher\ReloadLocalizationFinisher;
use TYPO3\CMS\Backend\Localization\LocalizationHandlerInterface;
use TYPO3\CMS\Backend\Localization\LocalizationInstructions;
use TYPO3\CMS\Backend\Localization\LocalizationMode;
use TYPO3\CMS\Backend\Localization\LocalizationResult;
use TYPO3\CMS\Backend\Localization\ManualLocalizationHandler;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Domain\Dto\InlineParentReference;
use WebVision\Deepltranslate\Core\Service\InlineRelationResolver;
use WebVision\Deepltranslate\Core\Service\RecordLocalizationResolverInterface;

/**
 * Decorates the "Manual" handler of the TYPO3 v14 localization wizard. A connected mode translation
 * ("Translate") of an inline (IRRE) child in connected mode is created through the translated parent
 * record, so TYPO3 attaches it to that parent. The content is copied as before, not translated.
 *
 * Everything else, including a free mode copy ("Copy"), is handed to the core handler unchanged.
 *
 * @internal No public API
 */
#[AsDecorator(
    decorates: ManualLocalizationHandler::class,
    onInvalid: ContainerInterface::IGNORE_ON_INVALID_REFERENCE,
)]
final readonly class InlineChildAwareManualLocalizationHandler implements LocalizationHandlerInterface
{
    public function __construct(
        #[AutowireDecorated]
        private LocalizationHandlerInterface $inner,
        private InlineRelationResolver $inlineRelationResolver,
        private RecordLocalizationResolverInterface $recordLocalizationResolver,
    ) {}

    public function getIdentifier(): string
    {
        return $this->inner->getIdentifier();
    }

    public function getLabel(): string
    {
        return $this->inner->getLabel();
    }

    public function getDescription(): string
    {
        return $this->inner->getDescription();
    }

    public function getIconIdentifier(): string
    {
        return $this->inner->getIconIdentifier();
    }

    public function isAvailable(LocalizationInstructions $instructions): bool
    {
        return $this->inner->isAvailable($instructions);
    }

    public function processLocalization(LocalizationInstructions $instructions): LocalizationResult
    {
        if ($instructions->mode !== LocalizationMode::TRANSLATE || $instructions->mainRecordType === 'pages') {
            return $this->inner->processLocalization($instructions);
        }
        $reference = $this->inlineRelationResolver
            ->resolveParentReference($instructions->mainRecordType, $instructions->recordUid)
            ->reference;
        if ($reference === null) {
            return $this->inner->processLocalization($instructions);
        }

        return $this->localizeThroughParent($reference, $instructions->targetLanguageId);
    }

    /**
     * Mirrors the core handler: an existing translation is left alone. Without a translated parent the
     * child translation could not be attached to anything, so nothing is created.
     */
    private function localizeThroughParent(InlineParentReference $reference, int $targetLanguageId): LocalizationResult
    {
        if ($this->recordLocalizationResolver->hasTranslation($reference->childTable, $reference->childUid, $targetLanguageId)) {
            return LocalizationResult::success(new NoopLocalizationFinisher());
        }
        if (!$this->recordLocalizationResolver->hasTranslation($reference->parentTable, $reference->parentUid, $targetLanguageId)) {
            return LocalizationResult::error([
                $this->getLanguageService()->sL(
                    'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:inline.error.parentNotTranslated'
                ),
            ]);
        }

        return $this->dispatchInlineLocalizeSynchronize($reference, $targetLanguageId);
    }

    /**
     * The DataHandler command the inline "localize" control of TYPO3 dispatches for a single child. It must
     * not carry an `action`, which makes TYPO3 localize every child not translated yet and ignore `ids`.
     */
    private function dispatchInlineLocalizeSynchronize(InlineParentReference $reference, int $targetLanguageId): LocalizationResult
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [
            $reference->parentTable => [
                $reference->parentUid => [
                    'inlineLocalizeSynchronize' => [
                        'field' => $reference->parentField,
                        'language' => $targetLanguageId,
                        'ids' => [$reference->childUid],
                    ],
                ],
            ],
        ]);
        $dataHandler->process_cmdmap();

        return $dataHandler->errorLog !== []
            ? LocalizationResult::error($dataHandler->errorLog)
            : LocalizationResult::success(new ReloadLocalizationFinisher());
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
