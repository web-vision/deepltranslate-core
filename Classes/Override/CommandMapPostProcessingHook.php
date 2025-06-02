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
            // b13/container reworked their DataHandler hook implementation and splitted the method into
            // dedicated methods, which needs to be handled here to allow multiple b13/container versions.
            // See:
            // - https://github.com/b13/container/issues/609
            // - https://github.com/b13/container/pull/617
            if (method_exists($this, 'localizeOrCopyToLanguage')) {
                $this->localizeOrCopyToLanguage($id, (int)$value, $command, $dataHandler);
            } elseif (method_exists($this, 'localizeChildren')) {
                $this->localizeChildren($id, (int)$value, $command, $dataHandler);
            } else {
                throw new \RuntimeException(
                    sprintf(
                        implode('', [
                            'Extension "%s" changed their internal DataHandler hook implementation "%s" again. Please ',
                            'open an issue on "%s" and report the issue so it can be adopted.',
                        ]),
                        'b13/container',
                        \B13\Container\Hooks\Datahandler\CommandMapPostProcessingHook::class,
                        'https://github.com/web-vision/deepltranslate-core/issues',
                    ),
                    1748860059,
                );
            }
        } else {
            parent::processCmdmap_postProcess($command, $table, $id, $value, $dataHandler, $pasteUpdate, $pasteDatamap);
        }
    }
}
