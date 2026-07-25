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
        'title' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'Title',
            'config' => [
                'type' => 'input',
                'size' => 30,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'title, --div--;meta, hidden, sys_language_uid, l10n_parent',
        ],
    ],
];
