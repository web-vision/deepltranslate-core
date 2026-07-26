..  include:: /Includes.rst.txt

Known issues
============

Translation options not shown
-----------------------------

When API key is not set, *deepltranslate_core* disables all functions.
Go to :ref:`Settings <extensionConfiguration>` and fix it. Clear cache
after this.

TYPO3 Core patch required (l10n_source)
---------------------------------------

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
`Gerrit 94915 (13.4) <https://review.typo3.org/c/Packages/TYPO3.CMS/+/94915>`__.
TYPO3 v12.4 has reached ELTS and never receives the fix upstream. Until the fix
is released for v13.4, and permanently for v12.4, instances have to apply it
through a Composer patch, for example with
`vaimo/composer-patches <https://github.com/vaimo/composer-patches>`__:

..  code-block:: json

    {
        "require-dev": {
            "vaimo/composer-patches": "^6.0.1"
        },
        "extra": {
            "patches": {
                "typo3/cms-backend": {
                    "TYPO3 #110281 l10n_source on v12.4 (ELTS, always)": {
                        "source": "patches/typo3-cms-backend-110281-v12-v13.patch",
                        "version": ">=12.4.0 <13.0.0"
                    },
                    "TYPO3 #110281 l10n_source on v13.4 (until the fix release)": {
                        "source": "patches/typo3-cms-backend-110281-v12-v13.patch",
                        "version": ">=13.4.0 <13.4.34"
                    }
                }
            }
        }
    }

See the general TYPO3 documentation on applying Composer patches for
project-specific setup details. The patch used by this extension:

..  literalinclude:: ../CorePatches/typo3-cms-backend-110281-v12-v13.patch
    :language: diff
    :caption: typo3-cms-backend-110281-v12-v13.patch
