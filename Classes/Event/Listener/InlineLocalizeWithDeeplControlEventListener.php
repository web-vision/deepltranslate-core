<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Event\Listener;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Form\Event\ModifyInlineElementControlsEvent;
use TYPO3\CMS\Backend\Form\Event\ModifyInlineElementEnabledControlsEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\MathUtility;
use WebVision\Deepltranslate\Core\Form\InlineLocalizeWithDeeplControlRendererInterface;
use WebVision\Deepltranslate\Core\Service\DeeplTranslateAvailabilityService;
use WebVision\Deepltranslate\Core\Service\InlineRelationResolver;
use WebVision\Deepltranslate\Core\Service\RecordLocalizationResolverInterface;

/**
 * Adds a "Translate with DeepL" control to each inline (IRRE) child of a translated parent record that
 * has no translation in the parent's language yet. Translating the child with DeepL localizes it through
 * its translated parent.
 *
 * @internal No public API
 */
final readonly class InlineLocalizeWithDeeplControlEventListener
{
    private const CONTROL_IDENTIFIER = 'deepltranslate';

    public function __construct(
        private DeeplTranslateAvailabilityService $deeplTranslateAvailabilityService,
        private InlineLocalizeWithDeeplControlRendererInterface $controlRenderer,
        private InlineRelationResolver $inlineRelationResolver,
        private RecordLocalizationResolverInterface $recordLocalizationResolver,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    #[AsEventListener('deepltranslate-core/inline-localize-with-deepl-control')]
    public function __invoke(ModifyInlineElementControlsEvent $event): void
    {
        $targetLanguageId = $this->resolveTargetLanguageId($event);
        if ($targetLanguageId === null) {
            return;
        }
        $table = $event->getForeignTable();
        $record = $event->getRecord();
        $uid = (int)($record['uid'] ?? 0);
        $pageId = (int)($record['pid'] ?? 0);
        if (!$this->isLocalizedThroughShownParent($event, $table, $uid)
            || $this->recordLocalizationResolver->hasTranslation($table, $uid, $targetLanguageId)
            || !$this->deeplTranslateAvailabilityService->isAvailableForRecord($this->getBackendUser(), $table, $pageId, $targetLanguageId)
        ) {
            return;
        }
        $control = $this->controlRenderer->render($event->getElementData(), $table, $uid, $targetLanguageId);
        if ($control !== '') {
            $event->setControl(self::CONTROL_IDENTIFIER, $control);
        }
    }

    /**
     * Returns the language of the translated parent for a child shown in it without a translation of its
     * own, in the same situation core offers its own localize control for. Returns null otherwise.
     */
    private function resolveTargetLanguageId(ModifyInlineElementControlsEvent $event): ?int
    {
        if (!$event->isVirtual()
            || !MathUtility::canBeInterpretedAsInteger($event->getParentUid())
            || !$this->isLocalizeControlEnabled($event)
        ) {
            return null;
        }
        $targetLanguageId = (int)($event->getFieldConfiguration()['inline']['parentSysLanguageUid'] ?? 0);

        return $targetLanguageId > 0 ? $targetLanguageId : null;
    }

    /**
     * Core decides on its own localize control with this event, which casts `appearance.enabledControls` to
     * boolean and lets listeners disable the control. The result is not passed on to the controls event, so
     * it is asked again for the same element.
     */
    private function isLocalizeControlEnabled(ModifyInlineElementControlsEvent $event): bool
    {
        /** @var ModifyInlineElementEnabledControlsEvent $enabledControlsEvent */
        $enabledControlsEvent = $this->eventDispatcher->dispatch(
            new ModifyInlineElementEnabledControlsEvent($event->getElementData(), $event->getRecord())
        );

        return $enabledControlsEvent->isControlEnabled('localize');
    }

    /**
     * The DeepL translation of an inline child is only attached to the translated parent when the relation
     * can be handed over to that parent: a `foreign_field` relation of exactly the field the child is shown
     * in. For other inline fields, comma separated lists or `MM` relations, the child would be translated
     * on its own and stay detached from the translated parent.
     */
    private function isLocalizedThroughShownParent(ModifyInlineElementControlsEvent $event, string $table, int $uid): bool
    {
        $reference = $this->inlineRelationResolver->resolveParentReference($table, $uid)->reference;
        $data = $event->getElementData();

        return $reference !== null
            && $reference->parentTable === ($data['inlineParentTableName'] ?? null)
            && $reference->parentField === ($data['inlineParentFieldName'] ?? null);
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
