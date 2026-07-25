<?php

declare(strict_types=1);

/**
 * Inline (IRRE) parent record, mirroring the TCA reported in
 * https://github.com/web-vision/deepltranslate-core/issues/503
 */
return [
    'ctrl' => [
        'title' => 'Inline relation parent',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'title' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'Title',
            'config' => [
                'type' => 'input',
                'size' => 30,
            ],
        ],
        'children_declared' => [
            'exclude' => true,
            'label' => 'Children (pointer field configured in TCA)',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_testinlinerelations_child_declared',
                'foreign_field' => 'parentid',
                'foreign_sortby' => 'sorting',
                'appearance' => [
                    'showSynchronizationLink' => true,
                    'showAllLocalizationLink' => true,
                    'showPossibleLocalizationRecords' => true,
                    'expandSingle' => true,
                    'enabledControls' => [
                        'localize' => true,
                    ],
                ],
            ],
        ],
        'children_undeclared' => [
            'exclude' => true,
            'label' => 'Children (pointer field not configured in TCA)',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_testinlinerelations_child_undeclared',
                'foreign_field' => 'parentid',
                'foreign_sortby' => 'sorting',
                'appearance' => [
                    'showSynchronizationLink' => true,
                    'showAllLocalizationLink' => true,
                    'showPossibleLocalizationRecords' => true,
                    'expandSingle' => true,
                    'enabledControls' => [
                        'localize' => true,
                    ],
                ],
            ],
        ],
        // Second field owning the same child table through a different pointer column, so a child
        // record carrying both pointers is claimed by two parent relations at once - an ambiguous,
        // broken configuration the resolver must refuse rather than guess.
        'children_declared_ambiguous' => [
            'exclude' => true,
            'label' => 'Children (ambiguous second relation to the declared child table)',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_testinlinerelations_child_declared',
                'foreign_field' => 'parentid_ambiguous',
                'foreign_sortby' => 'sorting',
                'appearance' => [
                    'expandSingle' => true,
                ],
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'title, children_declared, children_undeclared, children_declared_ambiguous, --div--;meta, hidden, sys_language_uid, l10n_parent',
        ],
    ],
];
