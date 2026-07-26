..  _important-typo3-core-l10n-source-patch:

==================================================
Important: TYPO3 Core patch required (l10n_source)
==================================================

Description
===========

Localizing a record whose table declares a translation source field
(``l10n_source``) - through the ``deepltranslate`` command or any other
``DataHandler::localize()`` call - can create a **duplicate translation** in the
same language. This is the root cause of the duplicated translations reported for
``web-vision/deepltranslate-auto-renew`` in `#42
<https://github.com/web-vision/deepltranslate-auto-renew/issues/42>`__.

The defect is in the TYPO3 Core, not in this extension:
``BackendUtility::getRecordLocalization()`` (and, on TYPO3 v14,
``LocalizationRepository::getRecordTranslation()``) matches existing translations
by ``l10n_source`` when the table defines one and does not fall back to
``l10n_parent``. A valid translation created without populating ``l10n_source``
(the usual result of a plain DataHandler datamap, an importer, a migration or
``MASK``) is not found, so ``DataHandler::localize()`` does not detect it and
creates a second one.

The fix is upstream in the TYPO3 Core:

*   Issue: `forge #110281 <https://forge.typo3.org/issues/110281>`__
*   main: `Gerrit 94914 <https://review.typo3.org/c/Packages/TYPO3.CMS/+/94914>`__
*   14.3: `Gerrit 94916 <https://review.typo3.org/c/Packages/TYPO3.CMS/+/94916>`__
*   13.4: `Gerrit 94915 <https://review.typo3.org/c/Packages/TYPO3.CMS/+/94915>`__

Until the fix is released, instances have to apply the Core fix through a
**Composer patch**. This extension applies it only for its own test runs (a
``require-dev`` patcher) and ships a regression test that fails without it.

Applying the patch in your instance
===================================

Use a Composer patch plugin - for example
`cweagans/composer-patches <https://github.com/cweagans/composer-patches>`__ or
`vaimo/composer-patches <https://github.com/vaimo/composer-patches>`__ - and
reference the patch matching your TYPO3 version (shown below), scoped so it only
applies to the affected versions:

..  code-block:: json

    {
        "require-dev": {
            "vaimo/composer-patches": "^6.0.1"
        },
        "extra": {
            "patches": {
                "typo3/cms-backend": {
                    "TYPO3 #110281 l10n_source on v13.4 (until the fix release)": {
                        "source": "patches/typo3-cms-backend-110281-v13.patch",
                        "version": ">=13.4.0 <13.4.34"
                    },
                    "TYPO3 #110281 l10n_source on v14.3 (until the fix release)": {
                        "source": "patches/typo3-cms-backend-110281-v14.patch",
                        "version": ">=14.3.0 <14.3.6"
                    }
                }
            }
        }
    }

Raise or drop the version bounds once the Core fix ships in a patch level
release. See the general TYPO3 documentation on applying Composer patches for
project-specific setup details.

The patch for TYPO3 v13.4
=========================

..  literalinclude:: ../../CorePatches/typo3-cms-backend-110281-v13.patch
    :language: diff
    :caption: typo3-cms-backend-110281-v13.patch

The patch for TYPO3 v14.3
=========================

..  literalinclude:: ../../CorePatches/typo3-cms-backend-110281-v14.patch
    :language: diff
    :caption: typo3-cms-backend-110281-v14.patch
