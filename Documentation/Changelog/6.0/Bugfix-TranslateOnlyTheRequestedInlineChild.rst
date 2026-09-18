..  _bugfix-translate-only-the-requested-inline-child-1790949312:

======================================================================
Bugfix: Translating one inline child no longer translates its siblings
======================================================================


Description
===========

Since 6.0.4 and 5.1.7, the DeepL translation of an inline child in connected
mode is handed over to
:php:`\TYPO3\CMS\Core\DataHandling\DataHandler::inlineLocalizeSynchronize()` of
the translated parent, see
:ref:`important-inline-child-records-are-translated-through-their-parent-1785623102`.

The command was sent with the action `localize` and the uid of the child in
`ids`. TYPO3 treats the two as alternatives: with an action, it localizes every
child of the parent field without a translation and ignores `ids`. Only `ids`
without an action localizes the given children, which is what the localize
button of a single inline record in TYPO3 sends.

So translating one inline child with DeepL, for example a card added to an
already translated card group, translated all untranslated children of the same
parent as well. Their content was sent to DeepL and billed.

The command now contains `ids` only.

Impact
======

Exactly the requested inline child is translated. Other children of the same
parent without a translation stay untranslated until they are translated
themselves.

See pull request `#672 <https://github.com/web-vision/deepltranslate-core/pull/672>`__.
