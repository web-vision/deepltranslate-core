<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function (): void {
    $columns = [
        'tx_wvdeepltranslate_content_not_checked' => [
            'exclude' => 0,
            'l10n_display' => 'hideDiff',
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:pages.tx_wvdeepltranslate_content_not_checked',
            'config' => [
                'type' => 'check',
                'items' => [
                    [
                        'label' => 'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:translated_with_deepl',
                    ],
                ],
            ],
        ],
    ];

    $columns['tx_wvdeepltranslate_translated_time'] = [
        'exclude' => 0,
        'l10n_display' => 'hideDiff',
        'displayCond' => 'FIELD:sys_language_uid:>:0',
        'label' => 'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:pages.tx_wvdeepltranslate_translated_time',
        'config' => [
            'type' => 'datetime',
            'format' => 'datetime',
            'readOnly' => true,
            'default' => 0,
        ],
    ];

    $columns['tx_wvdeepltranslate_context'] = [
        'exclude' => 1,
        'label' => 'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:pages.tx_wvdeepltranslate_context',
        'description' => 'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:pages.tx_wvdeepltranslate_context_description',
        'config' => [
            'type' => 'text',
            'rows' => 5,
            'cols' => 60,
            'max' => 3000,
        ],
    ];

    ExtensionManagementUtility::addTCAcolumns('pages', $columns);

    ExtensionManagementUtility::addFieldsToPalette(
        'pages',
        'deepl_translate',
        implode(',', [
            'tx_wvdeepltranslate_context',
            'tx_wvdeepltranslate_content_not_checked',
            'tx_wvdeepltranslate_translated_time',
        ])
    );

    ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        sprintf('--div--;%s,--palette--;;deepl_translate;', 'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:pages.deepl.tab.label'),
        '',
        'after:language'
    );
})();
