<?php

declare(strict_types=1);

/**
 * Inline (IRRE) child record with the `foreign_field` pointer column *not* configured in TCA at
 * all - only present in `ext_tables.sql`. This is the setup reported in
 * https://github.com/web-vision/deepltranslate-core/issues/503 and it behaves differently from
 * the `passthrough` variant, because `DataHandler::fillInFieldArray()` silently drops values of
 * columns which are unknown to the TCA schema.
 */
return [
    'ctrl' => [
        'title' => 'Inline relation child (pointer field not configured in TCA)',
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
    ],
    'types' => [
        '0' => [
            'showitem' => 'title, --div--;meta, hidden, sys_language_uid, l10n_parent',
        ],
    ],
];
