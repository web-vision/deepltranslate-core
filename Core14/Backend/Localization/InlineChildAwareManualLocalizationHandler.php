<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Core14\Backend\Localization;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TYPO3\CMS\Backend\Localization\Finisher\NoopLocalizationFinisher;
use TYPO3\CMS\Backend\Localization\LocalizationHandlerInterface;
use TYPO3\CMS\Backend\Localization\LocalizationInstructions;
use TYPO3\CMS\Backend\Localization\LocalizationMode;
use TYPO3\CMS\Backend\Localization\LocalizationResult;
use TYPO3\CMS\Backend\Localization\ManualLocalizationHandler;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Domain\Dto\InlineParentReference;
use WebVision\Deepltranslate\Core\Service\InlineRelationResolver;
use WebVision\Deepltranslate\Core\Service\RecordLocalizationResolverInterface;

/**
 * Decorates the "Manual Translation" handler of the TYPO3 v14 localization wizard. The translation
 * ("Translate") of an inline (IRRE) child in connected mode is created through the translated parent
 * record, so TYPO3 attaches it to that parent. The content is copied as before, not translated.
 *
 * The core handler localizes such a child on its own, which leaves the translation attached to the
 * default language parent, where no edit form of the translated parent shows it. The wizard is the
 * localize action of the list module in TYPO3 v14 as well, so this applies to every table.
 *
 * Everything else, including a free mode copy ("Copy"), is handed to the core handler unchanged.
 *
 * The decorator takes over the tag of the core handler. Its priority keeps the core handler in the first
 * place of the wizard, which preselects the first handler.
 *
 * @internal No public API
 */
#[AsDecorator(
    decorates: ManualLocalizationHandler::class,
    onInvalid: ContainerInterface::IGNORE_ON_INVALID_REFERENCE,
)]
#[AsTaggedItem(priority: 1)]
final readonly class InlineChildAwareManualLocalizationHandler implements LocalizationHandlerInterface
{
    public function __construct(
        #[AutowireDecorated]
        private LocalizationHandlerInterface $inner,
        private InlineRelationResolver $inlineRelationResolver,
        private RecordLocalizationResolverInterface $recordLocalizationResolver,
        private InlineChildLocalization $inlineChildLocalization,
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
        if (!$this->inlineChildLocalization->hasTranslatedParent($reference, $targetLanguageId)) {
            return $this->inlineChildLocalization->createParentNotTranslatedResult();
        }

        // The DataHandler command the inline "localize" control of TYPO3 dispatches for a single child. It
        // must not carry an `action`, which makes TYPO3 localize every child not translated yet and ignore
        // `ids`.
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

        return $this->inlineChildLocalization->createResult($reference, $targetLanguageId, $dataHandler->errorLog);
    }
}
