<?php

declare(strict_types=1);

/**
 * Inline (IRRE) parent embedding shared `tt_content` records, modelled after EXT:news
 * (`tx_news_domain_model_news.content_elements`): the same `tt_content` table is used both for
 * normal content elements placed directly on a page and, in connected mode, as inline children of
 * this record. The pointer column `tx_testinlinerelations_related` on `tt_content` is what tells
 * the two apart - see the `tt_content` override in this extension.
 *
 * @see https://github.com/georgringer/news/blob/main/Configuration/TCA/tx_news_domain_model_news.php
 */
return [
    'ctrl' => [
        'title' => 'Inline content element parent',
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
        'content_elements' => [
            'exclude' => true,
            'label' => 'Content elements',
            'config' => [
                'type' => 'inline',
                'allowed' => 'tt_content',
                'foreign_table' => 'tt_content',
                'foreign_field' => 'tx_testinlinerelations_related',
                'foreign_sortby' => 'sorting',
                'maxitems' => 99,
                'appearance' => [
                    'showSynchronizationLink' => true,
                    'showAllLocalizationLink' => true,
                    'showPossibleLocalizationRecords' => true,
                    'expandSingle' => true,
                    'enabledControls' => [
                        'localize' => true,
                    ],
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'title, content_elements, --div--;meta, hidden, sys_language_uid, l10n_parent',
        ],
    ],
];
