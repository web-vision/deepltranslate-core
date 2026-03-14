<?php

use WebVision\Deepltranslate\Core\Form\Item\SiteConfigSupportedLanguageItemsProcFunc;

(static function (): void {
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
