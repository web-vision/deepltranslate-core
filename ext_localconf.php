<?php

defined('TYPO3') or die();

(static function (): void {
    $typo3version = new \TYPO3\CMS\Core\Information\Typo3Version();
    $majorVersion = $typo3version->getMajorVersion();

    //allowLanguageSynchronizationHook manipulates l10n_state
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
        = \WebVision\Deepltranslate\Core\Hooks\AllowLanguageSynchronizationHook::class;

    //hook for translate content
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processTranslateToClass']['deepl']
        = \WebVision\Deepltranslate\Core\Hooks\TranslateHook::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['deepl']
        = \WebVision\Deepltranslate\Core\Hooks\TranslateHook::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][\WebVision\Deepltranslate\Core\Hooks\UsageProcessAfterFinishHook::class]
        = \WebVision\Deepltranslate\Core\Hooks\UsageProcessAfterFinishHook::class;

    //------------------------------------------------------------------------------------------------------------------
    // XClass DatabaseRecordList for rendering custom checkboxes to toggle deepl selection in RecordList module
    //------------------------------------------------------------------------------------------------------------------
    if ($majorVersion === 13) {
        // @todo Remove registration together with `Core13/Backend/RecordList/*` classes when TYPO3 v13 support is
        //       dropped in `web-vision/deepltranslate-core:7.0`.
        $hasActiveGridelementsDatabaseRecordListOverride = (
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('gridelements')
            && !empty($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['gridelements']['nestingInListModule'])
        );
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\RecordList\DatabaseRecordList::class] = [
            'className' => match (true) {
                // Use `EXT:gridelement` xclass'd to extend from it instead from TYPO3 core original class
                $hasActiveGridelementsDatabaseRecordListOverride => \WebVision\Deepltranslate\Core\Core13\Backend\RecordList\DatabaseRecordListWithGridelements::class,
                // Extend directly from TYPO3 original class
                default => \WebVision\Deepltranslate\Core\Core13\Backend\RecordList\DatabaseRecordListCore::class,
            },
        ];
    }
    //------------------------------------------------------------------------------------------------------------------

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('container')) {
        //xclass CommandMapPostProcessingHook for translating contents within containers
        if (class_exists(\B13\Container\Hooks\Datahandler\CommandMapPostProcessingHook::class)) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\B13\Container\Hooks\Datahandler\CommandMapPostProcessingHook::class] = [
                'className' => \WebVision\Deepltranslate\Core\Override\CommandMapPostProcessingHook::class,
            ];
        }
    }

    // We need to provide the global backend javascript module instead of calling page-renderer here directly - which
    // cannot be done and checking the context (FE/BE) directly. Instantiating PageRenderer here directly would be
    // emitted an exception as the cache configuration manager cannot be retrieved in this early stage.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][1684661135]
        = \WebVision\Deepltranslate\Core\Hooks\PageRendererHook::class . '->renderPreProcess';

    // Add caching for DeepL API-supported Languages
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['deepltranslate_core']
        ??= [];
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['deepltranslate_core']['backend']
        ??= \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class;

    $accessRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\WebVision\Deepltranslate\Core\Access\AccessRegistry::class);
    $accessRegistry->addAccess((new \WebVision\Deepltranslate\Core\Access\AllowedTranslateAccess()));
})();
