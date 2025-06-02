<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Event\Listener;

use WebVision\Deepl\Base\Controller\Backend\LocalizationController;
use WebVision\Deepl\Base\Event\LocalizationProcessPrepareDataHandlerCommandMapEvent;

/**
 * Listener for {@see LocalizationProcessPrepareDataHandlerCommandMapEvent} dispatched in
 * {@see LocalizationController::customProcess()} to process the introduced custom modes,
 * otherwise leading to an empty commandMap within the processing step of the localization
 * modal in PageLayout module.
 */
final class ProcessLocalizationModeEventListener
{
    public function __invoke(LocalizationProcessPrepareDataHandlerCommandMapEvent $event): void
    {
        // @todo Consider to drop `deepltranslateauto` mode.
        if (!in_array($event->getAction(), ['deepltranslate', 'deepltranslateauto'], true)
            || !$event->getLocalizationModes()->hasIdentifier($event->getAction())
        ) {
            // Not responsible, early return.
            return;
        }
        $cmd = $event->getCmd();
        foreach ($event->getUidList() as $currentUid) {
            $cmd['tt_content'][$currentUid] = [
                // Both modes are handled by the same custom DataHandler command
                'deepltranslate' => $event->getDestLanguageId(),
            ];
        }
        $event->setCmd($cmd);
    }
}
