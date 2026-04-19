<?php

(static function (): void {
    $GLOBALS['TCA']['pages']['columns']['nav_title']['l10n_mode'] = 'prefixLangTitle';
    $GLOBALS['TCA']['pages']['columns']['title']['config']['type'] = 'text';
})();
