..  _bugfix-inline-relation-pinned-to-a-language-1794813845:

==============================================
Bugfix: Inline relation pinned to one language
==============================================


Description
===========

Since 6.0.4 and 5.1.7 the DataHandler command `deepltranslate` hands an
inline child in connected mode over to its parent, so TYPO3 attaches the
translation to the translated parent. The
:php-short:`\WebVision\Deepltranslate\Core\Service\InlineRelationResolver`
counted every inline field with a `foreign_field` pointer as such a parent,
including fields whose `foreign_match_fields` pin the children to one
language:

..  code-block:: php

    'children' => [
        'config' => [
            'type' => 'inline',
            'foreign_table' => 'tt_content',
            'foreign_field' => 'tx_container_parent',
            'foreign_match_fields' => [
                'sys_language_uid' => 0,
            ],
        ],
    ],

Such a field holds records of that one language only. It is used, for
example, to edit the children of a b13/container grid inline, while the
translations of the children keep pointing at the grid in the default
language. A translation can never belong to it.
:php:`\TYPO3\CMS\Core\DataHandling\DataHandler::inlineLocalizeSynchronize()`
writes the `foreign_match_fields` onto every child it attaches. The
translated text was therefore saved with the pinned language `0` and the
uid of the translated parent: a record that shows up in no language.
Children of nested grids were skipped entirely, because their grid had
turned into such a record as well.

Impact
======

An inline field whose `foreign_match_fields` contain the language field of
the child table is no longer considered an inline parent. A record held by
it is localized on its own, as it was up to 6.0.3 and 5.1.6.

Inline fields with `foreign_match_fields` on other columns are unchanged.
