<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Form\Item\SiteConfigSupportedLanguageItemsProcFunc;

(static function (): void {
    $GLOBALS['SiteConfiguration']['site']['columns']['deeplContext'] = [
        'label' => 'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:site_configuration.deepl.field.context.label',
        'description' => 'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:site_configuration.deepl.field.context.description',
        'config' => [
            'type' => 'text',
            'rows' => 5,
            'cols' => 60,
            'max' => 3000,
        ],
    ];

    $GLOBALS['SiteConfiguration']['site']['palettes']['deepl'] = [
        'showitem' => 'deeplContext',
    ];

    $showItemList = GeneralUtility::trimExplode(',', $GLOBALS['SiteConfiguration']['site']['types']['0']['showitem']);
    $deeplTab = '--div--;LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:site_configuration.deepl.title';
    $deeplPalette = '--palette--;;deepl';

    if (!in_array($deeplPalette, $showItemList, true)) {
        // Determine whether any "DeepL"-flavoured tab divider already exists (e.g. the one
        // provided by deepltranslate-mass) so the context palette can be placed in that tab
        // and a duplicate tab is avoided regardless of extension load order.
        $existingDeeplTabIndex = null;
        foreach ($showItemList as $index => $entry) {
            if (str_starts_with($entry, '--div--;') && stripos($entry, 'deepl') !== false) {
                $existingDeeplTabIndex = $index;
                break;
            }
        }

        if ($existingDeeplTabIndex === null) {
            // Move the tab to the end of the list so no subsequently added fields
            // would end up on it.
            $showItemList[] = $deeplTab;
            $showItemList[] = $deeplPalette;
        } else {
            array_splice($showItemList, $existingDeeplTabIndex + 1, 0, [$deeplPalette]);
        }

        $GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] = implode(',', $showItemList);
    }
    $GLOBALS['SiteConfiguration']['site_language']['columns']['deeplTargetLanguage'] = [
        'label' => 'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:site_configuration.deepl.field.targetlanguage.label',
        'description' => 'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:site_configuration.deepl.field.targetlanguage.description',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'itemsProcFunc' => SiteConfigSupportedLanguageItemsProcFunc::class . '->getSupportedLanguageForField',
            'items' => [],
            'minitems' => 0,
            'maxitems' => 1,
            'size' => 1,
        ],
    ];

    $GLOBALS['SiteConfiguration']['site_language']['columns']['deeplFormality'] = [
        'label' => 'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:site_configuration.deepl.field.formality.label',
        'description' => 'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:site_configuration.deepl.field.formality.description',
        'displayCond' => [
            'AND' => [
                'USER:' . \WebVision\Deepltranslate\Core\Form\User\HasFormalitySupport::class . '->checkFormalitySupport',
            ],
        ],
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                [
                    'label' => 'default',
                    'value' => 'default',
                ],
                [
                    'label' => 'more formal language',
                    'value' => 'more',
                ],
                [
                    'label' => 'more informal language',
                    'value' => 'less',
                ],
                [
                    'label' => 'prefer more language, fallback default',
                    'value' => 'prefer_more',
                ],
                [
                    'label' => 'prefer informal language, fallback default',
                    'value' => 'prefer_less',
                ],
            ],
            'minitems' => 0,
            'maxitems' => 1,
            'size' => 1,
        ],
    ];

    $GLOBALS['SiteConfiguration']['site_language']['palettes']['deepl'] = [
        'showitem' => 'deeplTargetLanguage, deeplFormality',
    ];

    $GLOBALS['SiteConfiguration']['site_language']['types']['1']['showitem'] = str_replace(
        '--palette--;;default,',
        '--palette--;;default, --palette--;LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:site_configuration.deepl.title;deepl,',
        $GLOBALS['SiteConfiguration']['site_language']['types']['1']['showitem']
    );
})();
