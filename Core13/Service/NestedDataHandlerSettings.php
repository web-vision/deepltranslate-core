<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Core13\Service;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use WebVision\Deepltranslate\Core\Service\NestedDataHandlerSettingsInterface;

/**
 * TYPO3 v13 implementation of {@see NestedDataHandlerSettingsInterface}
 *
 * @internal
 * @todo Remove together with `Core13/*` when TYPO3 v13 support is dropped.
 */
#[AsAlias(id: NestedDataHandlerSettingsInterface::class)]
final class NestedDataHandlerSettings implements NestedDataHandlerSettingsInterface
{
    public function handOver(DataHandler $callingDataHandler, DataHandler $nestedDataHandler): void
    {
        // A localized record of a table with `hideAtCopy` is created hidden unless this is set. The backend sets it on
        // the outer DataHandler from the user setting.
        $nestedDataHandler->neverHideAtCopy = $callingDataHandler->neverHideAtCopy;
        $nestedDataHandler->copyTree = $callingDataHandler->copyTree;
        $nestedDataHandler->bypassWorkspaceRestrictions = $callingDataHandler->bypassWorkspaceRestrictions;
    }
}
