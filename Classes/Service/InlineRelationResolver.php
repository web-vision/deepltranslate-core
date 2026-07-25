<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
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
    public function __construct(
        private readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * Whether any TCA field can own records of the given table through a `foreign_field` pointer
     * column, meaning records of that table can be inline children in connected mode.
     *
     * In contrast to {@see self::resolveParentReference()} this does not need a record and can
     * therefore be used at points where the pointer column of a newly created child record has not
     * been written yet - which is the case for most of the DataHandler processing.
     */
    public function isPossibleInlineChildTable(string $childTable): bool
    {
        if (!$this->tcaSchemaFactory->has($childTable)) {
            return false;
        }
        foreach ($this->getInlineParentCandidates($childTable) as $ignored) {
            return true;
        }

        return false;
    }

    /**
     * Returns the connected mode inline parent of the given record, or null if the record is not an
     * inline child in connected mode, if the relation cannot be resolved unambiguously or if the
     * parent record does not exist.
     */
    public function resolveParentReference(string $childTable, int $childUid): ?InlineParentReference
    {
        if ($childUid <= 0 || !$this->tcaSchemaFactory->has($childTable)) {
            return null;
        }
        $childRecord = BackendUtility::getRecord($childTable, $childUid);
        if (!is_array($childRecord)) {
            return null;
        }

        $references = [];
        foreach ($this->getInlineParentCandidates($childTable) as $candidate) {
            $parentTable = $candidate['parentTable'];
            $configuration = $candidate['configuration'];
            $parentUid = $this->determineParentUid($childRecord, $parentTable, $configuration);
            if ($parentUid === null) {
                continue;
            }
            $reference = new InlineParentReference(
                childTable: $childTable,
                childUid: $childUid,
                parentTable: $parentTable,
                parentField: $candidate['parentField'],
                parentUid: $parentUid,
                foreignField: (string)$configuration['foreign_field'],
            );
            $references[$reference->getIdentifier()] = $reference;
        }

        if (count($references) !== 1) {
            // Either no connected mode inline parent at all, or an ambiguous relation configuration
            // which cannot be resolved reliably. Both must not be handled as inline child.
            return null;
        }

        return array_pop($references);
    }

    /**
     * All TCA fields owning records of the given table through a `foreign_field` pointer column.
     *
     * @return iterable<array{parentTable: non-empty-string, parentField: non-empty-string, configuration: array<string, mixed>}>
     */
    private function getInlineParentCandidates(string $childTable): iterable
    {
        foreach ($this->tcaSchemaFactory->get($childTable)->getPassiveRelations() as $passiveRelation) {
            $parentTable = $passiveRelation->fromTable();
            $parentField = $passiveRelation->fromField();
            if ($parentField === null
                || $parentField === ''
                || $parentTable === ''
                || !$this->tcaSchemaFactory->has($parentTable)
            ) {
                continue;
            }
            $parentSchema = $this->tcaSchemaFactory->get($parentTable);
            if (!$parentSchema->hasField($parentField)) {
                continue;
            }
            $configuration = $parentSchema->getField($parentField)->getConfiguration();
            if (($configuration['foreign_table'] ?? '') !== $childTable) {
                continue;
            }
            if (!empty($configuration['MM'])) {
                // Relations using an intermediate table do not have a pointer field on the child side.
                continue;
            }
            if ((string)($configuration['foreign_field'] ?? '') === '') {
                continue;
            }
            yield [
                'parentTable' => $parentTable,
                'parentField' => $parentField,
                'configuration' => $configuration,
            ];
        }
    }

    /**
     * @param array<string, mixed> $childRecord
     * @param array<string, mixed> $configuration
     */
    private function determineParentUid(
        array $childRecord,
        string $parentTable,
        array $configuration
    ): ?int {
        $foreignField = (string)$configuration['foreign_field'];
        if (!array_key_exists($foreignField, $childRecord)) {
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
