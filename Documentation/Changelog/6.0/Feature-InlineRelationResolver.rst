..  _feature-inline-relation-resolver-1785623247:

=====================================
Feature: New `InlineRelationResolver`
=====================================


Description
===========

`EXT:deepltranslate_core` provides the new public service
:php-short:`\WebVision\Deepltranslate\Core\Service\InlineRelationResolver` to
resolve the record owning an inline (IRRE) child record in connected mode, meaning
the parent TCA field uses `foreign_field` to point back from the child to its
parent.

This information is required to decide whether a record may be localized on its
own, or whether the localization has to be triggered through its parent record.

Example
-------

..  code-block:: php

    use WebVision\Deepltranslate\Core\Service\InlineRelationResolver;

    final readonly class MyService
    {
        public function __construct(
            private InlineRelationResolver $inlineRelationResolver,
        ) {}

        public function isInlineChild(string $table, int $uid): bool
        {
            return $this->inlineRelationResolver
                ->resolveParentReference($table, $uid)
                ->isResolved();
        }
    }

:php:`resolveParentReference()` always returns an
:php-short:`\WebVision\Deepltranslate\Core\Domain\Dto\InlineParentResolution`,
never :php:`null`. It carries the outcome as an
:php-short:`\WebVision\Deepltranslate\Core\Domain\Enum\InlineParentState` and, for
a resolved relation, the
:php-short:`\WebVision\Deepltranslate\Core\Domain\Dto\InlineParentReference` with
the child table and uid, the resolved parent table, parent field and parent uid,
and the name of the `foreign_field` pointer column.

The state lets a caller tell an ordinary standalone record apart from a broken
relation configuration, which matters because only the latter is worth reporting
to an editor:

..  list-table::
    :header-rows: 1

    *   -   State
        -   Meaning
    *   -   `Resolved`
        -   Inline child in connected mode, parent resolved unambiguously.
            :php:`isResolved()` returns :php:`true` and a reference is carried.
    *   -   `NotInlineChild`
        -   Not an inline child in connected mode - the common case for any
            standalone record. Localize normally, do not report.
    *   -   `Ambiguous`
        -   The record could be an inline child of more than one parent at the
            same time, for example when multiple parent tables point to the same
            child table without using `foreign_table_field`. Report it.
    *   -   `ParentMissing`
        -   The `foreign_field` points at a parent record which does not exist
            (any more). Report it.
    *   -   `ParentNotTranslatable`
        -   Inline child in connected mode, but the parent table is not
            translatable, most prominently `sys_file_metadata` owned by
            `sys_file`. There can never be a parent localization to attach to, so
            the child is localized on its own. Do not report.

Resolving the parent requires the `foreign_field` pointer column of the child
record to be written already, which is not the case during most of the
DataHandler processing of newly created child records. For those cases the
service additionally answers the record independent question whether a table can
be used as inline child at all:

..  code-block:: php

    $this->inlineRelationResolver->isPossibleInlineChildTable('sys_file_reference');
    // true - for example `tt_content.image` owns records of that table

Only parents which can carry a localization themselves are counted here. A table
owned exclusively by untranslatable parents - `sys_file_metadata`, owned by
`sys_file` - is not reported as a possible inline child, because handing its
localization to that parent could never produce anything. This matches the
`ParentNotTranslatable` state of the per-record resolution; both share one
predicate so they cannot drift apart.

Impact
======

Add-ons dispatching DeepL translations for arbitrary records can detect inline
child records without implementing their own TCA analysis.
