<?php

$majorVersion = (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion();
return [
    'dependencies' => ['core', 'backend'],
    'imports' => [
        '@typo3/backend/localization.js' => sprintf('EXT:deepltranslate_core/Resources/Public/JavaScript/localization%s.js', $majorVersion),
    ],
];
