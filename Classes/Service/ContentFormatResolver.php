<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use WebVision\Deepltranslate\Core\Domain\Enum\ContentFormat;

/**
 * Tells whether a record field holds rich text or plain text, respecting `columnsOverrides` of the record type,
 * for example `tt_content.bodytext` is rich text for the CType "text" only.
 */
#[Autoconfigure(public: true)]
final class ContentFormatResolver
{
    /**
     * @param array<string, mixed> $record
     */
    public function resolve(string $table, string $field, array $record): ContentFormat
    {
        $columnConfig = $GLOBALS['TCA'][$table]['columns'][$field]['config'] ?? null;
        if (!is_array($columnConfig)) {
            return ContentFormat::Unknown;
        }
        $recordType = (string)BackendUtility::getTCAtypeValue($table, $record);
        $config = array_replace_recursive(
            $columnConfig,
            $GLOBALS['TCA'][$table]['types'][$recordType]['columnsOverrides'][$field]['config'] ?? []
        );
        if (($config['type'] ?? '') === 'text' && (bool)($config['enableRichtext'] ?? false)) {
            return ContentFormat::RichText;
        }
        return ContentFormat::PlainText;
    }
}
