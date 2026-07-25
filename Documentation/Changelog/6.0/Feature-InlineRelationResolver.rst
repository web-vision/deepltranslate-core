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
            return $this->inlineRelationResolver->resolveParentReference($table, $uid) !== null;
        }
    }

The returned :php-short:`\WebVision\Deepltranslate\Core\Domain\Dto\InlineParentReference`
carries the child table and uid along with the resolved parent table, parent field,
parent uid and the name of the `foreign_field` pointer column.

:php:`null` is returned if the record is not an inline child in connected mode, if
the parent record does not exist or if the relation cannot be resolved
unambiguously, for example when multiple parent tables point to the same child
table without using `foreign_table_field`.

Resolving the parent requires the `foreign_field` pointer column of the child
record to be written already, which is not the case during most of the
DataHandler processing of newly created child records. For those cases the
service additionally answers the record independent question whether a table can
be used as inline child at all:

..  code-block:: php

    $this->inlineRelationResolver->isPossibleInlineChildTable('sys_file_reference');
    // true - for example `tt_content.image` owns records of that table

Impact
======

Add-ons dispatching DeepL translations for arbitrary records can detect inline
child records without implementing their own TCA analysis.
