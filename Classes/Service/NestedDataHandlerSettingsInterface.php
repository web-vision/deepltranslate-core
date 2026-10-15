<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use WebVision\Deepltranslate\Core\Core13\Service\NestedDataHandlerSettings as Core13NestedDataHandlerSettings;
use WebVision\Deepltranslate\Core\Core14\Service\NestedDataHandlerSettings as Core14NestedDataHandlerSettings;

/**
 * Hands the settings of a DataHandler to a DataHandler started within it that exist in one TYPO3 version only, the
 * way `DataHandler::getLocalTCE()` of that version does for `copyTree` and `bypassWorkspaceRestrictions`.
 * `neverHideAtCopy` is handed over as well, because the nested DataHandler copies the record itself. The settings both
 * versions know are handed over by the caller.
 *
 * TYPO3 v13 knows `neverHideAtCopy`, `copyTree` and `bypassWorkspaceRestrictions`. TYPO3 v14 removed them
 * (Breaking-107856 for the first two) and reads the "never hide at copy" setting from the backend user instead.
 *
 * {@see Core13NestedDataHandlerSettings}
 * {@see Core14NestedDataHandlerSettings}
 *
 * @internal
 */
interface NestedDataHandlerSettingsInterface
{
    public function handOver(DataHandler $callingDataHandler, DataHandler $nestedDataHandler): void;
}
