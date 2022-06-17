<?php
defined('TYPO3_MODE') or die();
//
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:wv_deepltranslate/Configuration/TsConfig/Page/pagetsconfig.tsconfig">'
);
//hook for translate content
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processTranslateToClass']['deepl'] = 'WebVision\\WvDeepltranslate\\Hooks\\TranslateHook';
//hook to checkModifyAccessList for editors
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList']['deepl'] = 'WebVision\\WvDeepltranslate\\Hooks\\TCEmainHook';
//hook for overriding localization.js,recordlist.js and including deepl.css
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']['deepl'] = 'WebVision\\WvDeepltranslate\\Hooks\\TranslateHook->executePreRenderHook';

//xclass localizationcontroller for localizeRecords() and process() action
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Backend\\Controller\\Page\\LocalizationController'] = [
    'className' => 'WebVision\\WvDeepltranslate\\Override\\LocalizationController',
];

//xclass databaserecordlist for rendering custom checkboxes to toggle deepl selection in recordlist
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Recordlist\\RecordList\\DatabaseRecordList'] = [
    'className' => 'WebVision\\WvDeepltranslate\\Override\\DatabaseRecordList',
];

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('container')) {
    //xclass CommandMapPostProcessingHook for translating contents within containers
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['B13\\Container\\Hooks\\Datahandler\\CommandMapPostProcessingHook'] = [
        'className' => 'WebVision\\WvDeepltranslate\\Override\\CommandMapPostProcessingHook',
    ];
}

if (TYPO3_MODE === 'BE') {
    $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
    $pageRenderer->loadRequireJsModule('TYPO3/CMS/WvDeepltranslate/Localization');
}
