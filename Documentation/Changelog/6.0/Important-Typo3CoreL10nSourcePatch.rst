..  _important-typo3-core-l10n-source-patch:

========================================================
Important: TYPO3 Core l10n_source fix, no Composer patch
========================================================

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

Nothing has to be done on TYPO3 v13.4 and v14.3
===============================================

The Core fix is released with TYPO3 **v13.4.34** and **v14.3.6**. This extension
requires at least these versions, so the fix is part of the TYPO3 Core on every
supported TYPO3 version. No patch has to be applied at all.

No Composer patch is declared anymore
=====================================

Earlier releases declared the Core patch in the ``extra.patches`` section of the
``composer.json`` of this extension. That declaration used the object form
(``{"source": …, "version": …}``), which only `vaimo/composer-patches
<https://github.com/vaimo/composer-patches>`__ understands. Projects using
`cweagans/composer-patches <https://github.com/cweagans/composer-patches>`__ in
turn aborted their Composer run with an *Array to string conversion* error
(1.7.3) or a type error in ``ResolverBase`` (2.0.0), see `#646
<https://github.com/web-vision/deepltranslate-core/issues/646>`__.

The extension therefore no longer declares and no longer applies any Composer
patch. The patch files below ``Documentation/CorePatches/`` are kept as
documentation and are shown at the end of this document.

Applying the patch on a pinned older TYPO3 Core
===============================================

Projects which pin an older TYPO3 patch level release and cannot update to
v13.4.34 or v14.3.6 right away can declare the patch themselves. Copy the patch
file matching the TYPO3 version in use from ``Documentation/CorePatches/`` of
``web-vision/deepltranslate-core`` into the project, for example into
``patches/``, and declare it in the root ``composer.json``.

With ``cweagans/composer-patches``
----------------------------------

The 1.x plugin expects a plain patch path or URL as string and has no version
handling, so only declare the patch as long as the pinned TYPO3 Core needs it:

..  code-block:: json

    {
        "extra": {
            "patches": {
                "typo3/cms-backend": {
                    "TYPO3 #110281 l10n_source": "patches/typo3-cms-backend-110281-v13.patch"
                }
            }
        }
    }

With ``vaimo/composer-patches``
-------------------------------

This plugin supports the object form and can therefore scope a patch to the
TYPO3 versions which are missing the fix:

..  code-block:: json

    {
        "extra": {
            "patches": {
                "typo3/cms-backend": {
                    "TYPO3 #110281 l10n_source on v13.4": {
                        "source": "patches/typo3-cms-backend-110281-v13.patch",
                        "version": ">=13.4.0 <13.4.34"
                    },
                    "TYPO3 #110281 l10n_source on v14.3": {
                        "source": "patches/typo3-cms-backend-110281-v14.patch",
                        "version": ">=14.3.0 <14.3.6"
                    }
                }
            }
        }
    }

Already existing patches
------------------------

Patches the project applies to ``typo3/cms-backend`` itself may need adoption:
they have to apply on top of the changes shown below, otherwise patching fails
and aborts the Composer run. The same is true for patches provided by other
extensions for the same file.

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
