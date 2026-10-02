<?php

defined('TYPO3') or die();

(static function (): void {
    $GLOBALS['TCA']['tt_content']['columns']['subheader']['l10n_mode'] = 'prefixLangTitle';

    // The content element "Plain HTML" is edited in the code editor of TYPO3 13 and of `typo3/cms-t3editor` on
    // TYPO3 12, both declare the format `html`. Without `typo3/cms-t3editor` its text is a plain textarea, the
    // format is declared here, so it is translated as HTML with `<script>` and `<style>` kept, see
    // `ContentFormatResolver`. FormEngine renders a text field without render type as textarea and does not read
    // `format`, so the editor sees no difference. Framework extensions are loaded first, a render type set by
    // them is kept.
    if (is_array($GLOBALS['TCA']['tt_content']['types']['html'] ?? null)
        && !isset($GLOBALS['TCA']['tt_content']['types']['html']['columnsOverrides']['bodytext']['config']['renderType'])
        && !isset($GLOBALS['TCA']['tt_content']['types']['html']['columnsOverrides']['bodytext']['config']['format'])
    ) {
        $GLOBALS['TCA']['tt_content']['types']['html']['columnsOverrides']['bodytext']['config']['format'] = 'html';
    }
})();
