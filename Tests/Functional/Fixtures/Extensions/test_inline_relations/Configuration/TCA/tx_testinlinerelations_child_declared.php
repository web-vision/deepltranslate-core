<?php

declare(strict_types=1);

/**
 * Inline (IRRE) child record with the `foreign_field` pointer column configured as
 * `type => passthrough` in TCA - the variant used by EXT:styleguide.
 */
return [
    'ctrl' => [
        'title' => 'Inline relation child (pointer field configured in TCA)',
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
        'parentid' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        // Second pointer column, used only to construct an ambiguous relation (a child owned by two
        // parent fields at once), see `tx_testinlinerelations_parent.children_declared_ambiguous`.
        'parentid_ambiguous' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'title' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'Title',
            'config' => [
                'type' => 'input',
                'size' => 30,
            ],
        ],
        // Plain text here, rich text only through the `overrideChildTca` of
        // `tx_testinlinerelations_parent.children_declared`, like the accordion items of the dev theme.
        'description' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'Description',
            'config' => [
                'type' => 'text',
            ],
        ],
        // Nested inline relation, see `tx_testinlinerelations_grandchild`.
        'grandchildren' => [
            'exclude' => true,
            'label' => 'Grandchildren',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_testinlinerelations_grandchild',
                'foreign_field' => 'childid',
                'foreign_sortby' => 'sorting',
                'appearance' => [
                    'showPossibleLocalizationRecords' => true,
                    'expandSingle' => true,
                ],
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'title, description, grandchildren, --div--;meta, hidden, sys_language_uid, l10n_parent',
        ],
    ],
];
