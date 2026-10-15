<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Core14\Service;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use WebVision\Deepltranslate\Core\Service\NestedDataHandlerSettingsInterface;

/**
 * TYPO3 v14 implementation of {@see NestedDataHandlerSettingsInterface}
 *
 * TYPO3 v14 has no setting of its own to hand over. The "never hide at copy" setting is read from the backend user,
 * which the nested DataHandler gets from the calling one.
 *
 * @internal
 */
#[AsAlias(id: NestedDataHandlerSettingsInterface::class)]
final class NestedDataHandlerSettings implements NestedDataHandlerSettingsInterface
{
    public function handOver(DataHandler $callingDataHandler, DataHandler $nestedDataHandler): void {}
}
