<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use WebVision\Deepltranslate\Core\Domain\Dto\InlineParentReference;
use WebVision\Deepltranslate\Core\Domain\Dto\InlineParentResolution;

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
     * Resolves the connected mode inline parent of the given record.
     *
     * The returned {@see InlineParentResolution} distinguishes a normal standalone record - for
     * example a `tt_content` element placed directly on a page, which shares its table with inline
     * children but is not one - from a broken relation configuration, so the caller can localize the
     * former silently and report the latter to the editor.
     */
    public function resolveParentReference(string $childTable, int $childUid): InlineParentResolution
    {
        if ($childUid <= 0 || !$this->tcaSchemaFactory->has($childTable)) {
            return InlineParentResolution::notInlineChild();
        }
        $childRecord = BackendUtility::getRecord($childTable, $childUid);
        if (!is_array($childRecord)) {
            return InlineParentResolution::notInlineChild();
        }

        $references = [];
        $parentMissing = false;
        $parentNotTranslatable = false;
        foreach ($this->getInlineParentCandidates($childTable) as $candidate) {
            $parentTable = $candidate['parentTable'];
            $configuration = $candidate['configuration'];
            $parentUid = $this->determineParentUid($childRecord, $parentTable, $configuration);
            if ($parentUid === null) {
                // The record is not attached to this parent field (pointer empty, or a table/match
                // field discriminator excludes it) - not an inline child through this candidate.
                continue;
            }
            if (!$this->tcaSchemaFactory->get($parentTable)->isLanguageAware()) {
                // `sys_file` owns `sys_file_metadata` through a `foreign_field` pointer, but
                // `sys_file` itself is not translatable. There can never be a parent localization
                // to attach the child to, so handing the localization over to
                // `DataHandler::inlineLocalizeSynchronize()` would create nothing at all. Such a
                // child is localized on its own instead. Checked before looking the parent record
                // up: it saves a query and keeps a non-translatable parent from being reported as
                // a missing one.
                $parentNotTranslatable = true;
                continue;
            }
            if (!is_array(BackendUtility::getRecord($parentTable, $parentUid))) {
                // The record points at a parent record that does not exist (any more).
                $parentMissing = true;
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

        if (count($references) > 1) {
            // The record could be an inline child of more than one parent at the same time, which
            // cannot be resolved reliably - a broken or conflicting relation configuration.
            return InlineParentResolution::ambiguous();
        }
        if (count($references) === 1) {
            return InlineParentResolution::resolved(array_pop($references));
        }
        if ($parentNotTranslatable) {
            // Takes precedence over `$parentMissing`: if the parent table cannot be translated at
            // all, the hand-over never applies and a dangling pointer is not worth reporting.
            return InlineParentResolution::parentNotTranslatable();
        }
        if ($parentMissing) {
            return InlineParentResolution::parentMissing();
        }

        return InlineParentResolution::notInlineChild();
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
        if ($parentUid <= 0) {
            return null;
        }

        return $parentUid;
    }
}
