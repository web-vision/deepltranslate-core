<?php

declare(strict_types=1);

namespace WebVision\WvDeepltranslate\Resolver;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class RichtextAllowTagsResolver
{
    private Richtext $richtext;

    public function __construct(
        ?Richtext $richtext = null
    ) {
        $this->richtext = $richtext ?? GeneralUtility::makeInstance(Richtext::class);
    }

    public function resolve(string $tableName, int $recordId, string $fieldName): array
    {
        if (!isset($GLOBALS['TCA'][$tableName]['columns'])) {
            throw new \RuntimeException('TCA Columns ist not defined', 1689950520561);
        }

        $field = $GLOBALS['TCA'][$tableName]['columns'][$fieldName];

        if (!isset($field['config']['type'])) {
            return [];
        }

        if ($field['config']['type'] !== 'text') {
            return [];
        }

        if (isset($field['config']['enableRichtext'])) {
            if ($field['config']['enableRichtext'] === false) {
                return [];
            }
        } elseif (isset($GLOBALS['TCA'][$tableName]['types']['columnsOverrides'][$fieldName]['config']['enableRichtext'])) {
            if ($GLOBALS['TCA'][$tableName]['types']['columnsOverrides'][$fieldName]['config']['enableRichtext'] === false) {
                return [];
            }
        }

        $record = BackendUtility::getRecord($tableName, $recordId);

        $allowTags = [];
        $rteConfig = $this->richtext->getConfiguration($tableName, $fieldName, $record['pid'], $record['CType'], $field['config']);
        if (isset($rteConfig['processing']['allowTags'])) {
            $allowTags = array_unique(array_merge($allowTags, $rteConfig['processing']['allowTags']), SORT_REGULAR);
        }

        return $allowTags;
    }
}
