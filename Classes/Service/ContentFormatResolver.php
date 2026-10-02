<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use WebVision\Deepltranslate\Core\Domain\Enum\ContentFormat;

/**
 * Tells whether a record field holds rich text, plain text or code, respecting `columnsOverrides` of the record type,
 * for example `tt_content.bodytext` is rich text for the CTypes "text" and "html" (HTML in a code editor) and plain
 * text for the CType "bullets".
 *
 * The `overrideChildTca` of the inline parent field can switch the rich text editor of an inline child on, as the
 * theme of the dev environment does for accordion items, or off. It applies if the record is the inline child of
 * exactly one parent record, see {@see InlineRelationResolver::resolveParentReference()}.
 */
#[Autoconfigure(public: true)]
final readonly class ContentFormatResolver
{
    public function __construct(
        private InlineRelationResolver $inlineRelationResolver,
    ) {}

    /**
     * @param array<string, mixed> $record
     */
    public function resolve(string $table, string $field, array $record): ContentFormat
    {
        $tca = $GLOBALS['TCA'][$table] ?? null;
        if (!is_array($tca) || !is_array($tca['columns'][$field]['config'] ?? null)) {
            return ContentFormat::Unknown;
        }
        // As in FormEngine, the `overrideChildTca` of the inline parent field applies first, the `columnsOverrides`
        // of the record type after it (`InlineOverrideChildTca` before `TcaColumnsOverrides`).
        $overrideChildTca = $this->findOverrideChildTca($table, $record);
        if ($overrideChildTca !== []) {
            $tca = array_replace_recursive($tca, $overrideChildTca);
        }
        return $this->contentFormatOf($this->fieldConfiguration($tca, $table, $field, $record));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function contentFormatOf(array $config): ContentFormat
    {
        if (($config['type'] ?? '') !== 'text') {
            return ContentFormat::PlainText;
        }
        if (($config['renderType'] ?? '') === 'codeEditor') {
            // HTML is the default mode of the code editor. Its text is translated, the translator keeps `<script>`
            // and `<style>` as they are. Other code, for example TypoScript, is not translated at all.
            $format = (string)($config['format'] ?? 'html');
            $mode = str_contains($format, '/') ? substr($format, (int)strrpos($format, '/') + 1) : $format;
            return $mode === 'html' ? ContentFormat::RichText : ContentFormat::Code;
        }
        if ((bool)($config['enableRichtext'] ?? false)) {
            return ContentFormat::RichText;
        }
        return ContentFormat::PlainText;
    }

    /**
     * @param array<string, mixed> $tca
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function fieldConfiguration(array $tca, string $table, string $field, array $record): array
    {
        // A table without type field has the type "0" in FormEngine (`DatabaseRecordTypeValue`), while
        // `getTCAtypeValue()` of TYPO3 v14 returns "1" for it, the schema of such a table has no sub schemas.
        $recordType = isset($tca['ctrl']['type'])
            ? (string)BackendUtility::getTCAtypeValue($table, $record)
            : (isset($tca['types']['0']) ? '0' : '1');
        return array_replace_recursive(
            $tca['columns'][$field]['config'] ?? [],
            $tca['types'][$recordType]['columnsOverrides'][$field]['config'] ?? []
        );
    }

    /**
     * Returns the `overrideChildTca` of the inline parent field, with the `columnsOverrides` of the parent's type.
     *
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function findOverrideChildTca(string $table, array $record): array
    {
        // Every field is resolved, the record is looked up only for tables used for inline children.
        if (!$this->inlineRelationResolver->isPossibleInlineChildTable($table)) {
            return [];
        }
        $reference = $this->inlineRelationResolver->resolveParentReference($table, (int)($record['uid'] ?? 0))->reference;
        if ($reference === null) {
            return [];
        }
        $parentTca = $GLOBALS['TCA'][$reference->parentTable] ?? null;
        $parentRecord = BackendUtility::getRecord($reference->parentTable, $reference->parentUid);
        if (!is_array($parentTca) || !is_array($parentRecord)) {
            return [];
        }
        $overrideChildTca = $this->fieldConfiguration($parentTca, $reference->parentTable, $reference->parentField, $parentRecord)['overrideChildTca'] ?? [];
        return is_array($overrideChildTca) ? $overrideChildTca : [];
    }
}
