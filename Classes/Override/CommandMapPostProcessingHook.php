<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Override;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class takes care of content translation for elements within containers
 */
class CommandMapPostProcessingHook extends \B13\Container\Hooks\Datahandler\CommandMapPostProcessingHook
{
    public function processCmdmap_postProcess(
        string $command,
        string $table,
        $id,
        $value,
        DataHandler $dataHandler,
        $pasteUpdate,
        $pasteDatamap
    ): void {
        if (!MathUtility::canBeInterpretedAsInteger($id) || (int)$id === 0) {
            return;
        }
        $id = (int)$id;
        if ($table === 'tt_content' && $command === 'deepltranslate') {
            $this->localizeOrCopyToLanguage($id, (int)$value, $command, $dataHandler);
        } else {
            parent::processCmdmap_postProcess($command, $table, $id, $value, $dataHandler, $pasteUpdate, $pasteDatamap);
        }
    }
}
