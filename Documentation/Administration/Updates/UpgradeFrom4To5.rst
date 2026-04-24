.. upgrade5to6:

==================
Upgrade 4.x to 5.x
==================

Starting with 5.x the composer package name and extension key has been renamed!

You need to migrate the extension settings from
``['TYPO3_CONF_VARS']['EXTENSIONS']['wv_deepltranslate']`` to
``['TYPO3_CONF_VARS']['EXTENSIONS']['deepltranslate_core']``.

Then you will need to replace the previous package by uninstalling it first.

composer-mode
~~~~~~~~~~~~~

..  code-block:: bash

    composer remove "web-vision/wv_deepltranslate"
    composer require "web-vision/deepltranslate-core":"^5"

classic-mode
~~~~~~~~~~~~

#.  **Uninstall "wv_deepltranslate" using the Extension Manager**.
    Switch to the module :guilabel:`Admin Tools > Extensions` and filter for
    :guilabel:`wv_deepltranslate` and remove (uninstall) the extension.

#.  **Ensure to remove the folder completely**.
    Run

    ..  code-block:: bash

        rm -rf typo3conf/ext/wv_deepltranslate

#.  **Get it from the Extension Manager**:
    Switch to the module :guilabel:`Admin Tools > Extensions`.
    Switch to :guilabel:`Get Extensions` and search for the extension key
    *deepltranslate_core* and import the extension from the repository.

#.  **Get it from typo3.org**:
    You can always get current version from `TER`_ by downloading the zip
    version. Upload the file afterwards in the Extension Manager.

..  _TER: https://extensions.typo3.org/extension/deepltranslate_core
..  _GITHUB_RELEASES: https://github.com/web-vision/deepltranslate-core/releases/
