<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Hooks;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Changes the `l10n_state` for fields with allowLanguageSynchronisation enabled to custom.
 * This is required to allow overriding the translation.
 */
#[Autoconfigure(public: true)]
final class AllowLanguageSynchronizationHook
{
    public function processDatamap_beforeStart(DataHandler $dataHandler): void
    {
        foreach ($dataHandler->datamap as $table => $elements) {
            foreach ($elements as $key => $element) {
                // element already exists, ignore
                if (MathUtility::canBeInterpretedAsInteger($key)) {
                    continue;
                }
                $l10nState = [];
                foreach ($element as $column => $value) {
                    if (!isset($GLOBALS['TCA'][$table]['columns'][$column])) {
                        continue;
                    }

                    $columnConfig = $GLOBALS['TCA'][$table]['columns'][$column];

                    if ((bool)($columnConfig['config']['behaviour']['allowLanguageSynchronization'] ?? false) === true) {
                        $l10nState[$column] = (($columnConfig['l10n_mode'] ?? '') === 'prefixLangTitle')
                            ? 'custom'
                            : 'parent';
                    }
                }
                if (!empty($l10nState)) {
                    // @todo Use flag `JSON_THROW_ON_ERROR` and deal with json encoding issues.
                    $element['l10n_state'] = json_encode($l10nState);
                    $dataHandler->datamap[$table][$key] = $element;
                }
            }
        }
    }
}
