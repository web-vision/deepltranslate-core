<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Event\Listener;

use TYPO3\CMS\Backend\Form\Event\ModifyInlineElementControlsEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\MathUtility;
use WebVision\Deepltranslate\Core\Form\InlineLocalizeWithDeeplControlRendererInterface;
use WebVision\Deepltranslate\Core\Service\DeeplTranslateAvailabilityService;

/**
 * Adds a "Localize with DeepL" control to each inline (IRRE) child of a translated parent record that
 * has no translation in the parent's language yet. Localizing the child with DeepL localizes it through
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
        $pageId = (int)($record['pid'] ?? 0);
        if (!$this->deeplTranslateAvailabilityService->isAvailableForRecord($this->getBackendUser(), $table, $pageId, $targetLanguageId)) {
            return;
        }

        $event->setControl(
            self::CONTROL_IDENTIFIER,
            $this->controlRenderer->render($event->getElementData(), $table, (int)$record['uid'], $targetLanguageId)
        );
    }

    /**
     * Returns the language of the translated parent for a child shown in it without a translation of its
     * own, in the same situation core offers its own localize control for. Returns null otherwise.
     */
    private function resolveTargetLanguageId(ModifyInlineElementControlsEvent $event): ?int
    {
        $fieldConfiguration = $event->getFieldConfiguration();
        if (!$event->isVirtual()
            || !MathUtility::canBeInterpretedAsInteger($event->getParentUid())
            || ($fieldConfiguration['appearance']['enabledControls']['localize'] ?? true) === false
        ) {
            return null;
        }
        $targetLanguageId = (int)($fieldConfiguration['inline']['parentSysLanguageUid'] ?? 0);

        return $targetLanguageId > 0 ? $targetLanguageId : null;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
