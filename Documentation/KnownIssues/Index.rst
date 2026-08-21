..  include:: /Includes.rst.txt

Known issues
============

Translation options not shown
-----------------------------

When API key is not set, *deepltranslate_core* disables all functions.
Go to :ref:`Settings <extensionConfiguration>` and fix it. Clear cache
after this.

TYPO3 Core patch may be required (l10n_source)
----------------------------------------------

Localizing a record whose table declares a translation source field
(``l10n_source``) - for example content elements built with ``MASK`` - can create
a **duplicate translation** in the same language. This is the root cause of the
duplicated translations reported for ``web-vision/deepltranslate-auto-renew`` in
`#42 <https://github.com/web-vision/deepltranslate-auto-renew/issues/42>`__.

The defect is in the TYPO3 Core, not in this extension:
``BackendUtility::getRecordLocalization()`` matches existing translations by
``l10n_source`` when the table defines one and does not fall back to
``l10n_parent``. A valid translation created without populating ``l10n_source``
(the usual result of a plain DataHandler datamap, an importer, a migration or
``MASK``) is not found, so ``DataHandler::localize()`` creates a second one.

The fix is upstream: `forge #110281 <https://forge.typo3.org/issues/110281>`__,
`Gerrit 94915 (13.4) <https://review.typo3.org/c/Packages/TYPO3.CMS/+/94915>`__
and released with TYPO3 **v13.4.34**. This extension requires at least that
version on the TYPO3 v13 side, so **nothing has to be done** on TYPO3 v13.

TYPO3 **v12.4 has reached ELTS and never receives the fix**. Instances on
TYPO3 v12.4 have to apply the patch themselves.

No Composer patch is declared any more
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Earlier releases declared the patch in the ``extra.patches`` section of the
``composer.json`` of this extension. That declaration used the object form with
a ``source`` and a ``version`` key, which only
`vaimo/composer-patches <https://github.com/vaimo/composer-patches>`__
understands. Patch declarations are collected from installed dependencies as
well, so projects using
`cweagans/composer-patches <https://github.com/cweagans/composer-patches>`__
aborted their Composer run with an "Array to string conversion" error (1.7.3) or
a type error in ``ResolverBase`` (2.0.0). See
`deepltranslate-core#646 <https://github.com/web-vision/deepltranslate-core/issues/646>`__.

The extension therefore **no longer declares or applies** any Composer patch.
The patch file below ``Documentation/CorePatches/`` stays in the repository as
documentation.

Applying the patch in a project
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Copy ``Documentation/CorePatches/typo3-cms-backend-110281-v12-v13.patch`` from
`web-vision/deepltranslate-core <https://github.com/web-vision/deepltranslate-core>`__
into the project - for example into its ``patches/`` directory - and declare it
for ``typo3/cms-backend``.

With ``cweagans/composer-patches`` the patch is declared as a plain path or URL
string:

..  code-block:: json

    {
        "extra": {
            "patches": {
                "typo3/cms-backend": {
                    "TYPO3 #110281 l10n_source": "patches/typo3-cms-backend-110281-v12-v13.patch"
                }
            }
        }
    }

``vaimo/composer-patches`` additionally understands the object form, which can
scope a patch to the affected TYPO3 versions:

..  code-block:: json

    {
        "extra": {
            "patches": {
                "typo3/cms-backend": {
                    "TYPO3 #110281 l10n_source": {
                        "source": "patches/typo3-cms-backend-110281-v12-v13.patch",
                        "version": ">=12.4.0 <13.0.0"
                    }
                }
            }
        }
    }

Patches the project applies to ``typo3/cms-backend`` itself may need adoption:
they have to apply on top of the changes shown below, otherwise patching fails
and aborts the Composer run. The same is true for patches provided by other
extensions for the same file.

See the general TYPO3 documentation on applying Composer patches for
project-specific setup details. The patch shipped by this extension:

..  literalinclude:: ../CorePatches/typo3-cms-backend-110281-v12-v13.patch
    :language: diff
    :caption: typo3-cms-backend-110281-v12-v13.patch
