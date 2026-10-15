..  _bugfix-inline-child-translations-keep-the-settings-of-the-operation-1792069529:

====================================================================
Bugfix: Inline child translations keep the settings of the operation
====================================================================


Description
===========

The DataHandler command `deepltranslate` for an inline child localizes it
through its translated parent, with a DataHandler of its own. That
DataHandler did not get the settings of the DataHandler running the
command, which TYPO3 hands to the DataHandlers it starts itself.

An import that translated inline children, a seed for example, ran the
localization as a regular save. DataHandler hooks that skip imports, the
one of `web-vision/deepltranslate-auto-renew` for example, acted on it. A
DataHandler with logging disabled still got `sys_log` entries for the
child translation, a bypassed record access check was done again, and the
history records of the translation got a correlation id of their own.

In TYPO3 v13, "Translate with DeepL" for an inline child of a table with
`hideAtCopy`, a nested content element for example, created a hidden
translation, although the backend user setting `neverHideAtCopy` was
enabled.

Impact
======

The DataHandler localizing an inline child gets the import flag, the
logging setting, the record access check bypass and the correlation id of
the DataHandler running the `deepltranslate` command. In TYPO3 v13 it also
gets its "never hide at copy", copy tree and workspace restriction bypass
settings. TYPO3 v14 reads "never hide at copy" from the backend user, which
the DataHandler gets from the one running the command.

TYPO3 v13 starts the DataHandler that copies a record with a new
correlation id. The history record of the created translation does not
carry the correlation id of the operation there. TYPO3 v14 hands it on.

See pull request `#700
<https://github.com/web-vision/deepltranslate-core/pull/700>`__.
