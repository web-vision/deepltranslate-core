<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Override\Core12;

use WebVision\Deepltranslate\Core\Access\AllowedTranslateAccess;
use WebVision\Deepltranslate\Core\Event\DisallowTableFromDeeplTranslateEvent;
use WebVision\Deepltranslate\Core\Utility\DeeplBackendUtility;

/**
 * Class for rendering of Web>List module
 *
 * @internal
 * @override
 */
trait DatabaseRecordList
{
    /**
     * Creates the localization panel
     *
     * @param string $table The table
     * @param mixed[] $row The record for which to make the localization panel.
     * @param array[] $translations
     * @return string
     */
    public function makeLocalizationPanel($table, $row, array $translations): string
    {
        $out = parent::makeLocalizationPanel($table, $row, $translations);

        if (!DeeplBackendUtility::isDeeplApiKeySet()) {
            return $out;
        }

        $tableDisallowedEvent = new DisallowTableFromDeeplTranslateEvent($table);
        $this->eventDispatcher->dispatch($tableDisallowedEvent);
        if ($tableDisallowedEvent->isTranslateButtonsAllowed() === false) {
            return $out;
        }

        if (!$this->getBackendUserAuthentication()->check('custom_options', AllowedTranslateAccess::ALLOWED_TRANSLATE_OPTION_VALUE)) {
            return $out;
        }

        $pageId = (int)($table === 'pages' ? $row['uid'] : $row['pid']);
        // All records excluding pages
        $possibleTranslations = $this->possibleTranslations;
        if ($table === 'pages') {
            // Calculate possible translations for pages
            $possibleTranslations = array_map(static fn ($siteLanguage) => $siteLanguage->getLanguageId(), $this->languagesAllowedForUser);
            $possibleTranslations = array_filter($possibleTranslations, static fn ($languageUid) => $languageUid > 0);
        }
        $languageInformation = $this->translateTools->getSystemLanguages($pageId);
        foreach ($possibleTranslations as $lUid_OnPage) {
            if ($this->isEditable($table)
                && !$this->isRecordDeletePlaceholder($row)
                && !isset($translations[$lUid_OnPage])
                && $this->getBackendUserAuthentication()->checkLanguageAccess($lUid_OnPage)
                && DeeplBackendUtility::checkCanBeTranslated($pageId, $lUid_OnPage)
            ) {
                $out .= DeeplBackendUtility::buildTranslateButton(
                    $table,
                    $row['uid'],
                    $lUid_OnPage,
                    $this->listURL(),
                    $languageInformation[$lUid_OnPage]['title'],
                    $languageInformation[$lUid_OnPage]['flagIcon']
                );
            }
        }

        return $out;
    }
}
