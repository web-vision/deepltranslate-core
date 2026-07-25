<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// Pointer column back to the inline parent `tx_testinlinerelations_contentparent`, mirroring how
// EXT:news registers `tx_news_related_news` on `tt_content` as `type => passthrough`. A content
// element placed directly on a page leaves this at `0`; only a genuine inline child carries the
// parent uid. This is exactly the value the InlineRelationResolver uses to tell them apart.
ExtensionManagementUtility::addTCAcolumns('tt_content', [
    'tx_testinlinerelations_related' => [
        'label' => 'tx_testinlinerelations_related',
        'config' => [
            'type' => 'passthrough',
        ],
    ],
]);
