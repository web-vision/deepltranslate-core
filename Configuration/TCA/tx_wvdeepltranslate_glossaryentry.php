<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:glossaryentry',
        'label' => 'term',
        'iconfile' => 'EXT:deepltranslate_core/Resources/Public/Icons/deepl.svg',
        'default_sortby' => 'term ASC',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'hideTable' => false,
        'versioningWS' => false,
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'term',
    ],
    'inferface' => [
        'showRecordFieldList' => '',
        'maxDBListItems' => 20,
        'maxSingleDBListItems' => 100,
    ],
    'palettes' => [
    ],
    'types' => [
        '1' => [
            'showitem' => 'hidden,term',
        ],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'label' : 0) => '',
                        ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'value' : 1) => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l10n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'label' : 0) => '',
                        ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'value' : 1) => 0,
                    ],
                ],
                'foreign_table' => 'tx_wvdeepltranslate_glossaryentry',
                'foreign_table_where' =>
                    'AND {#tx_wvdeepltranslate_glossaryentry}.{#pid}=###CURRENT_PID###'
                    . ' AND {#tx_wvdeepltranslate_glossaryentry}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'term' => [
            'label' => 'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:entry.source',
            'l10n_mode' => '',
            'config' => (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12
                ? [
                    'type' => 'input',
                    'required' => true,
                    'eval' => 'trim',
                ]
                : [
                    'type' => 'input',
                    'eval' => 'trim,reqiured',
                ],
        ],
    ],
];
