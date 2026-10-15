<?php

declare(strict_types=1);

defined('TYPO3') or die();

$GLOBALS['TCA']['tx_testinlinerelations_child_declared']['ctrl']['hideAtCopy'] = true;
// TYPO3 v12 does not add the column of `enablecolumns.disabled` itself. Without it, DataHandler drops the hidden
// state of the copy.
$GLOBALS['TCA']['tx_testinlinerelations_child_declared']['columns']['hidden'] ??= [
    'exclude' => true,
    'config' => [
        'type' => 'check',
        'default' => 0,
    ],
];
