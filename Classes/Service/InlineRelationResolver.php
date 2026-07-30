<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
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
    /**
     * TCA field types which can own child records through a `foreign_field` pointer column.
     *
     * @var non-empty-string[]
     */
    private const RELATION_FIELD_TYPES = ['inline', 'file'];

    /**
     * Whether any TCA field can own records of the given table through a `foreign_field` pointer
     * column, meaning records of that table can be inline children in connected mode whose
     * localization has to be handed over to their parent.
     *
     * Only parents which can carry a localization themselves count. A table owned exclusively by
     * untranslatable parents - `sys_file_metadata`, owned by `sys_file` - is not reported, because
     * the hand-over could never produce anything for it and such a child has to be localized on
     * its own.
     *
     * In contrast to {@see self::resolveParentReference()} this does not need a record and can
     * therefore be used at points where the pointer column of a newly created child record has not
     * been written yet - which is the case for most of the DataHandler processing.
     */
    public function isPossibleInlineChildTable(string $childTable): bool
    {
        if (!isset($GLOBALS['TCA'][$childTable])) {
            return false;
        }
        foreach ($this->getInlineParentCandidates($childTable) as $candidate) {
            if ($this->parentCanCarryLocalization($candidate['parentTable'])) {
                return true;
            }
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
        if ($childUid <= 0 || !isset($GLOBALS['TCA'][$childTable])) {
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
            if (!$this->parentCanCarryLocalization($parentTable)) {
                // `sys_file` owns `sys_file_metadata` through a `foreign_field` pointer, but
                // `sys_file` itself is not translatable. There can never be a parent localization
                // to attach the child to, so handing the localization over to
                // `DataHandler::inlineLocalizeSynchronize()` would create nothing at all. Such a
                // child is localized on its own instead. Checked before looking the parent record
                // up: it saves a query and keeps a non-translatable parent from being reported as
                // a missing one. Mirrors `TcaSchema::isLanguageAware()` of TYPO3 v13, which is not
                // available on v12.
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
     * Whether the given parent table can carry a localization at all, meaning a localized inline
     * child could be attached to a translated parent record.
     *
     * `sys_file` is the prominent counter example: it owns `sys_file_metadata` through a
     * `foreign_field` pointer, but has neither `languageField` nor `transOrigPointerField`, so a
     * hand-over to `DataHandler::inlineLocalizeSynchronize()` could never produce anything.
     *
     * Mirrors `TcaSchema::isLanguageAware()` of TYPO3 v13, which is not available on v12.
     */
    private function parentCanCarryLocalization(string $parentTable): bool
    {
        $parentCtrl = $GLOBALS['TCA'][$parentTable]['ctrl'] ?? [];

        return isset($parentCtrl['languageField'], $parentCtrl['transOrigPointerField']);
    }

    /**
     * All TCA fields owning records of the given table through a `foreign_field` pointer column.
     *
     * @return iterable<array{parentTable: non-empty-string, parentField: non-empty-string, configuration: array<string, mixed>}>
     */
    private function getInlineParentCandidates(string $childTable): iterable
    {
        foreach (($GLOBALS['TCA'] ?? []) as $parentTable => $tableConfiguration) {
            foreach (($tableConfiguration['columns'] ?? []) as $parentField => $columnConfiguration) {
                $configuration = $columnConfiguration['config'] ?? [];
                if (!in_array($configuration['type'] ?? '', self::RELATION_FIELD_TYPES, true)) {
                    continue;
                }
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
                if ((string)$parentTable === '' || (string)$parentField === '') {
                    continue;
                }
                yield [
                    'parentTable' => (string)$parentTable,
                    'parentField' => (string)$parentField,
                    'configuration' => $configuration,
                ];
            }
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
