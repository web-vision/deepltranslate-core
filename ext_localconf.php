<?php

defined('TYPO3') or die();

(static function (): void {
    $typo3version = new \TYPO3\CMS\Core\Information\Typo3Version();

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

    //xclass localizationcontroller for localizeRecords() and process() action
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Controller\Page\LocalizationController::class] = [
        'className' => \WebVision\Deepltranslate\Core\Override\LocalizationController::class,
    ];

    //xclass databaserecordlist for rendering custom checkboxes to toggle deepl selection in recordlist
    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('gridelements') && !empty($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['gridelements']['nestingInListModule'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\RecordList\DatabaseRecordList::class] = [
            'className' => \WebVision\Deepltranslate\Core\Override\Core12\DatabaseRecordListWithGridelements::class,
        ];
    } else {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\RecordList\DatabaseRecordList::class] = [
            'className' => \WebVision\Deepltranslate\Core\Override\Core12\DatabaseRecordListCore::class,
        ];
    }

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('container')) {
        //xclass CommandMapPostProcessingHook for translating contents within containers
        if (class_exists(\B13\Container\Hooks\Datahandler\CommandMapPostProcessingHook::class)) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\B13\Container\Hooks\Datahandler\CommandMapPostProcessingHook::class] = [
                'className' => \WebVision\Deepltranslate\Core\Override\CommandMapPostProcessingHook::class,
            ];
        }
    }

    // TYPO3 v13 changed the markup and UX of the PageLayout translation modal which breaks with longer icon labels.
    // This has been reported to the core and a bugfix is in the making, but we ship a workaround for now but load
    // the custom backend css only.
    //
    // Using custom backend css loading introduced with: https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.3/Feature-100232-LoadAdditionalStylesheetsInTYPO3Backend.html
    //
    // @todo Remove this with TYPO3 v13.4.3 release => https://review.typo3.org/c/Packages/TYPO3.CMS/+/87576
    //
    if ($typo3version->getMajorVersion() === 13
        && version_compare($typo3version->getVersion(), '13.4.3', '<')
    ) {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets']['web-vision/deepltranslate-core']
            = 'EXT:deepltranslate_core/Resources/Public/Css/patch-105853.css';
    }

    // We need to provide the global backend javascript module instead of calling page-renderer here directly - which
    // cannot be done and checking the context (FE/BE) directly. Instantiating PageRenderer here directly would be
    // emitted an exception as the cache configuration manager cannot be retrieved in this early stage.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][1684661135]
        = \WebVision\Deepltranslate\Core\Hooks\PageRendererHook::class . '->renderPreProcess';

    //add caching for DeepL API-supported Languages
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['deepltranslate_core']
        ??= [];
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['deepltranslate_core']['backend']
        ??= \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class;

    $accessRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\WebVision\Deepltranslate\Core\Access\AccessRegistry::class);
    $accessRegistry->addAccess((new \WebVision\Deepltranslate\Core\Access\AllowedTranslateAccess()));
})();
