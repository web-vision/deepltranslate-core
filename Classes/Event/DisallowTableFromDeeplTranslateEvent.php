<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Event;

use TYPO3\CMS\Backend\RecordList\DatabaseRecordList;

/**
 * Represents an event to disallow a specific database table from DeepL translation.
 * Allows control over whether translation buttons are permitted for the specified table.
 *
 * Fired in
 *
 * * {@see DatabaseRecordList::makeLocalizationPanel()} for TYPO3 v13 to verify if
 *   automatic deepltranslate buttons shoud be added to the `List Module` for the
 *   `$tableName` table.
 *
 * @todo Consider to possible throw this event in TYPO3 v14 specific translation hanlder
 *       `TranslationHandlerInterface->isAllowed()` or similar, in that case to hide the
 *       handler as possible translation.
 */
final class DisallowTableFromDeeplTranslateEvent
{
    public function __construct(
        public readonly string $tableName,
        private bool $translateButtonsAllowed,
    ) {}

    public function isTranslateButtonsAllowed(): bool
    {
        return $this->translateButtonsAllowed;
    }

    public function disallowTranslateButtons(): void
    {
        $this->translateButtonsAllowed = false;
    }
}
