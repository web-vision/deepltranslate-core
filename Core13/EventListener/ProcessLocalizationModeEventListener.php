<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Core13\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use WebVision\Deepl\Base\Controller\Backend\LocalizationController;
use WebVision\Deepl\Base\Event\LocalizationProcessPrepareDataHandlerCommandMapEvent;

/**
 * Listener for {@see LocalizationProcessPrepareDataHandlerCommandMapEvent} dispatched in
 * {@see LocalizationController::customProcess()} to process the introduced custom modes,
 * otherwise leading to an empty commandMap within the processing step of the localization
 * modal in PageLayout module.
 *
 * @depreacted used only for TYPO3 v13 compatibility and will not be dispatched for TYPO3 v14
 *             and should be resort to TYPO3 v14 localization handler feature provided by the
 *             TYPO3 Core.
 */
final class ProcessLocalizationModeEventListener
{
    #[AsEventListener(
        identifier: 'deepltranslate-core/deepltranslate-core-localization-modes-process',
        after: 'deepl-base/process-default-typo3-localization-modes',
    )]
    public function __invoke(LocalizationProcessPrepareDataHandlerCommandMapEvent $event): void
    {
        if (!in_array($event->getAction(), ['deepltranslate'], true)
            || !$event->getLocalizationModes()->hasIdentifier($event->getAction())
        ) {
            // Not responsible, early return.
            return;
        }
        $cmd = $event->getCmd();
        foreach ($event->getUidList() as $currentUid) {
            $cmd['tt_content'][$currentUid] = [
                'deepltranslate' => $event->getDestLanguageId(),
            ];
        }
        $event->setCmd($cmd);
    }
}
