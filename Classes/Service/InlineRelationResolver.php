<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use WebVision\Deepltranslate\Core\Domain\Dto\InlineParentReference;

/**
 * Resolves the record owning an inline (IRRE) child record in connected mode, meaning the parent
 * TCA field uses `foreign_field` to point back from the child to its parent.
 *
 * This information is required to localize such a child record on its own: TYPO3 only writes the
 * `foreign_field` pointer of a localized child when the localization is triggered through the
 * parent record. Localizing the child directly - through `DataHandler::localize()` - creates a
 * translation which is not attached to the translated parent and therefore invisible.
 *
 * @see https://github.com/web-vision/deepltranslate-core/issues/503
 */
#[Autoconfigure(public: true)]
final class InlineRelationResolver
{
    /**
     * TCA field types which can own child records through a `foreign_field` pointer column.
     *
     * @var non-empty-string[]
     */
    private const RELATION_FIELD_TYPES = ['inline', 'file'];

    /**
     * Returns the connected mode inline parent of the given record, or null if the record is not an
     * inline child in connected mode, if the relation cannot be resolved unambiguously or if the
     * parent record does not exist.
     */
    public function resolveParentReference(string $childTable, int $childUid): ?InlineParentReference
    {
        if ($childUid <= 0 || !isset($GLOBALS['TCA'][$childTable])) {
            return null;
        }
        $childRecord = BackendUtility::getRecord($childTable, $childUid);
        if (!is_array($childRecord)) {
            return null;
        }

        $references = [];
        foreach (($GLOBALS['TCA'] ?? []) as $parentTable => $tableConfiguration) {
            foreach (($tableConfiguration['columns'] ?? []) as $parentField => $columnConfiguration) {
                $configuration = $columnConfiguration['config'] ?? [];
                if (!in_array($configuration['type'] ?? '', self::RELATION_FIELD_TYPES, true)) {
                    continue;
                }
                $parentUid = $this->determineParentUid($childTable, $childRecord, (string)$parentTable, $configuration);
                if ($parentUid === null) {
                    continue;
                }
                $reference = new InlineParentReference(
                    childTable: $childTable,
                    childUid: $childUid,
                    parentTable: (string)$parentTable,
                    parentField: (string)$parentField,
                    parentUid: $parentUid,
                    foreignField: (string)$configuration['foreign_field'],
                );
                $references[$reference->getIdentifier()] = $reference;
            }
        }

        if (count($references) !== 1) {
            // Either no connected mode inline parent at all, or an ambiguous relation configuration
            // which cannot be resolved reliably. Both must not be handled as inline child.
            return null;
        }

        return array_pop($references);
    }

    /**
     * @param array<string, mixed> $childRecord
     * @param array<string, mixed> $configuration
     */
    private function determineParentUid(
        string $childTable,
        array $childRecord,
        string $parentTable,
        array $configuration
    ): ?int {
        if (($configuration['foreign_table'] ?? '') !== $childTable) {
            return null;
        }
        if (!empty($configuration['MM'])) {
            // Relations using an intermediate table do not have a pointer field on the child side.
            return null;
        }
        $foreignField = (string)($configuration['foreign_field'] ?? '');
        if ($foreignField === '' || !array_key_exists($foreignField, $childRecord)) {
            return null;
        }
        // Child tables shared by multiple parent tables carry the parent table name in a dedicated column.
        $foreignTableField = (string)($configuration['foreign_table_field'] ?? '');
        if ($foreignTableField !== ''
            && (string)($childRecord[$foreignTableField] ?? '') !== $parentTable
        ) {
            return null;
        }
        // Child tables shared by multiple fields of the same parent table are distinguished by additional columns.
        foreach (($configuration['foreign_match_fields'] ?? []) as $matchField => $matchValue) {
            if ((string)($childRecord[$matchField] ?? '') !== (string)$matchValue) {
                return null;
            }
        }
        $parentUid = (int)($childRecord[$foreignField] ?? 0);
        if ($parentUid <= 0 || !is_array(BackendUtility::getRecord($parentTable, $parentUid))) {
            return null;
        }

        return $parentUid;
    }
}
