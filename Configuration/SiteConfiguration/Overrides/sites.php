<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
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

    // EXT:deepltranslate_mass and EXT:deepltranslate_auto_renew add a DeepL tab as well and reuse one of their two
    // entries only. Both are loaded after this extension, which they depend on, so the entry of one of them is added
    // when it is installed, and the site form keeps a single DeepL tab.
    $palette = '--palette--;;deepl';
    $deeplTabs = [
        'deepltranslate_core' => '--div--;LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:site_configuration.deepl.tab',
        'deepltranslate_mass' => '--div--;LLL:EXT:deepltranslate_mass/Resources/Private/Language/locallang.xlf:site.tab.deepl',
        'deepltranslate_auto_renew' => '--div--;LLL:EXT:deepltranslate_auto_renew/Resources/Private/Language/locallang.xlf:site.tab.deepl',
    ];
    $showItems = GeneralUtility::trimExplode(',', $GLOBALS['SiteConfiguration']['site']['types']['0']['showitem']);
    if (!in_array($palette, $showItems, true)) {
        $deeplTabPosition = null;
        foreach ($showItems as $position => $showItem) {
            if (in_array($showItem, $deeplTabs, true)) {
                $deeplTabPosition = $position;
                break;
            }
        }
        if ($deeplTabPosition === null) {
            $deeplTab = $deeplTabs['deepltranslate_core'];
            foreach (['deepltranslate_mass', 'deepltranslate_auto_renew'] as $extensionKey) {
                if (ExtensionManagementUtility::isLoaded($extensionKey)) {
                    $deeplTab = $deeplTabs[$extensionKey];
                    break;
                }
            }
            $showItems[] = $deeplTab;
            $showItems[] = $palette;
        } else {
            // At the end of the existing DeepL tab, which is not necessarily the last tab
            $insertPosition = count($showItems);
            foreach ($showItems as $position => $showItem) {
                if ($position > $deeplTabPosition && str_starts_with($showItem, '--div--')) {
                    $insertPosition = $position;
                    break;
                }
            }
            array_splice($showItems, $insertPosition, 0, [$palette]);
        }
        $GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] = implode(',', $showItems);
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
