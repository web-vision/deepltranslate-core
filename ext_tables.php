<?php

use WebVision\Deepltranslate\Core\Access\AccessRegistry;

defined('TYPO3') or die();

(function () {
    /** @var AccessRegistry $accessRegistry */
    $accessRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(AccessRegistry::class);
    $GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions']['deepltranslate'] ??= [];
    $GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions']['deepltranslate']['header'] = 'Deepl Translate Access';
    foreach ($accessRegistry->getAllAccess() as $access) {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions']['deepltranslate']['items'][$access->getIdentifier()] = [
            $access->getTitle(),
            $access->getIconIdentifier(),
            $access->getDescription(),
        ];
    }
})();
