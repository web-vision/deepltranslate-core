..  _bugfix-inline-child-translations-are-in-the-reference-index-1792066831:

============================================================
Bugfix: Inline child translations are in the reference index
============================================================


Description
===========

The DataHandler command `deepltranslate` for an inline child localizes it
through its translated parent, with a DataHandler of its own. That is the
case for "Translate with DeepL" of an inline child, the localization
wizard, `web-vision/deepltranslate-mass` and the inline translation of
`web-vision/deepltranslate-auto-renew`. TYPO3 updates the reference index
at the end of the outermost DataHandler only, and this DataHandler is
never the outermost.

The child translation and the relation of the translated parent to it
were missing from the reference index. The file reference of a translated
content element, for example, was not counted as a use of the file, and
a check of the reference index reported both records.

Impact
======

The reference index is updated for the records written when an inline
child is translated with DeepL. The DataHandler localizing the child works
with the backend user of the DataHandler that runs the command.

Inline children translated before this version keep the reference index
they had. Update the reference index once to correct it:

..  code-block:: bash

    vendor/bin/typo3 referenceindex:update

See pull request `#698
<https://github.com/web-vision/deepltranslate-core/pull/698>`__.
