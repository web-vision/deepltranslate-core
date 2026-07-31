..  _important-inline-child-records-are-translated-through-their-parent-1785623102:

=================================================================
Important: Inline child records are translated through the parent
=================================================================


Description
===========

Dispatching the `deepltranslate` DataHandler command for an inline (IRRE) child
record in connected mode - meaning the parent TCA field uses `foreign_field` -
created a translation which was not attached to the translated parent record.

The reason is that TYPO3 only writes the `foreign_field` pointer of a localized
child record when the localization has been triggered through the parent record.
:php:`\TYPO3\CMS\Core\DataHandling\DataHandler::localize()` called for the child
record itself either copies the pointer to the *default language* parent record or
drops it completely, if the pointer column is not configured in TCA. In both cases
the created translation is invisible in the backend.

The `deepltranslate` command now detects such records and hands them over to the
`inlineLocalizeSynchronize` DataHandler command of the resolved parent record, so
TYPO3 writes the `foreign_field` pointer, the `pid` and the sorting of the created
translation.

Impact
======

Translating an inline child record - either directly through the localization
wizard or dispatched by an add-on such as `web-vision/deepltranslate-auto-renew` -
now results in a translation which is correctly attached to the translated parent
record.

If the parent record has no translation for the target language yet, the child
record translation cannot be attached to anything. Nothing is created in that case
and a message is written to the system log, mirroring the behaviour of
:php:`\TYPO3\CMS\Core\DataHandling\DataHandler::inlineLocalizeSynchronize()`.
Translate the parent record first, which localizes its children along with it.

This applies only to parents which can be translated at all. An inline child whose
parent table carries no language configuration - `sys_file_metadata`, owned by the
untranslatable `sys_file` - is not handed over and is localized on its own
instead, because there could never be a parent localization to attach it to. See
:ref:`bugfix-inline-child-of-untranslatable-parent-1785481012`.
