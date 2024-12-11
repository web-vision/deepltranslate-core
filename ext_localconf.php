<?php

defined('TYPO3') or die();

(static function (): void {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['wvDeepltranslate_formalityUpgrade']
        = \WebVision\Deepltranslate\Core\Upgrades\FormalityUpgradeWizard::class;

    //allowLanguageSynchronizationHook manipulates l10n_state
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
        = \WebVision\Deepltranslate\Core\Hooks\AllowLanguageSynchronizationHook::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
        = \WebVision\Deepltranslate\Core\Hooks\Glossary\UpdatedGlossaryEntryTermHook::class;

    //hook for translate content
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processTranslateToClass']['deepl']
        = \WebVision\Deepltranslate\Core\Hooks\TranslateHook::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['deepl']
        = \WebVision\Deepltranslate\Core\Hooks\TranslateHook::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][\WebVision\Deepltranslate\Core\Hooks\UsageProcessAfterFinishHook::class]
        = \WebVision\Deepltranslate\Core\Hooks\UsageProcessAfterFinishHook::class;

    //hook to checkModifyAccessList for editors
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList']['deepl']
        = \WebVision\Deepltranslate\Core\Hooks\TCEmainHook::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all']['deepl-1675946132'] =
        \WebVision\Deepltranslate\Core\Hooks\DeeplPreviewFlagGeneratePageHook::class . '->renderDeeplPreviewFlag';

    //xclass localizationcontroller for localizeRecords() and process() action
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Controller\Page\LocalizationController::class] = [
        'className' => \WebVision\Deepltranslate\Core\Override\LocalizationController::class,
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Controller\RecordListController::class] = [
        'className' => \WebVision\Deepltranslate\Core\Override\Core12\DeeplRecordListController::class,
    ];
    //xclass databaserecordlist for rendering custom checkboxes to toggle deepl selection in recordlist
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\RecordList\DatabaseRecordList::class] = [
        'className' => \WebVision\Deepltranslate\Core\Override\Core12\DatabaseRecordList::class,
    ];

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

    //add caching for DeepL API-supported Languages
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['wvdeepltranslate']
        ??= [];
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['wvdeepltranslate']['backend']
        ??= \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class;

    $accessRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\WebVision\Deepltranslate\Core\Access\AccessRegistry::class);
    $accessRegistry->addAccess((new \WebVision\Deepltranslate\Core\Access\AllowedTranslateAccess()));
    $accessRegistry->addAccess((new \WebVision\Deepltranslate\Core\Access\AllowedGlossarySyncAccess()));
})();
