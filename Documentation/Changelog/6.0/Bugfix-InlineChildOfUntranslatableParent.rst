..  _bugfix-inline-child-of-untranslatable-parent-1785481012:

================================================
Bugfix: Inline child of an untranslatable parent
================================================


Description
===========

Between 6.0.4 and 6.0.5 - and 5.1.7 to 5.1.8 on the 5.1 line - translating file
metadata created nothing at all.

`sys_file` declares `sys_file_metadata` as a connected mode inline child through
a `foreign_field` pointer:

..  code-block:: php

    // typo3/cms-core Configuration/TCA/sys_file.php
    'metadata' => [
        'config' => [
            'type' => 'inline',
            'foreign_table' => 'sys_file_metadata',
            'foreign_field' => 'file',
        ],
    ],

but `sys_file` itself has neither `languageField` nor `transOrigPointerField` and
can therefore never carry a localization, while `sys_file_metadata` has both.

:php-short:`\WebVision\Deepltranslate\Core\Service\InlineRelationResolver` checked
only that a candidate parent field points at the child table, carries no `MM` and
has a non-empty `foreign_field`. It did not ask whether the parent table can be
translated at all. Metadata records were therefore treated as inline children and
their localization was handed over to
:php:`\TYPO3\CMS\Core\DataHandling\DataHandler::inlineLocalizeSynchronize()` on the
parent - which, without a parent localization, creates nothing. That behaviour is
correct and documented, so the translated metadata record simply never appeared.

Two fixes were needed, because the wrong answer was given at two levels:

*   The per-record resolution now reports the new state
    :php:`\WebVision\Deepltranslate\Core\Domain\Enum\InlineParentState::ParentNotTranslatable`
    for an inline child whose parent table is not translatable. It carries no
    reference and is not a broken relation, so the hook falls through to the
    regular localization and the child is localized on its own - the behaviour
    from before the inline relation resolution was introduced.

*   :php:`isPossibleInlineChildTable()` now counts only parents which can carry a
    localization themselves. Callers use it to decide whether a record has to be
    localized through its parent, and they have to decide that before the pointer
    column of a newly created child record is written, so the per-record
    resolution is not available to them. For `sys_file_metadata`, owned
    exclusively by the untranslatable `sys_file`, the old answer made callers
    skip the record entirely - which kept file metadata untranslated even with
    the per-record resolution fixed.

Both checks share one predicate, so they cannot drift apart.

Impact
======

File metadata is translated again, as it was up to 6.0.3 and 5.1.6.

More generally, an inline child in connected mode whose parent table is not
translatable is localized on its own instead of being handed to a parent which
can never carry a localization. The state is deliberately silent - it is a
regular record configuration, not a broken relation, and is not reported to the
editor.

Detecting whether a table can be an inline child at all is otherwise unchanged.

Released in 6.0.6 and 5.1.9. See issue `#618
<https://github.com/web-vision/deepltranslate-core/issues/618>`__.
