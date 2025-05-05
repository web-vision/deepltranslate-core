..  include:: /Includes.rst.txt

..  _updates:

=======
Updates
=======

Version 4.x > 5.x
=================

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

Version 3.x > 4.x
=================

If you are upgrading from 3.x on TYPO3 11 LTS to 12 LTS and you have used the site
config setup for translations, you can simply update.

Upgrade with Core Upgrade
-------------------------

If you are upgrading from a TYPO3 version below v11, you need to define the target
languages in the site configuration. See :ref:`sitesetup<Site Setup section>`
in this documentation.

Version 2.x > 3.x
=================

..  note:: This Upgrade is only needed, if you are using glossary functionality.

Run the Upgrade wizard shipped with version 3. The wizard only appears, if necessary:

..  figure:: /Images/Administration/upgrade-wizard-v3.png
    :alt: Screenshot ob backend Upgrade wizard

This wizard moves your glossaries to the new structure, fixes backend group
rights and changes the module name.

After this, you have to run a GlossarySync update, either by CLI or by backend

#.  :ref:`sync-cli`
#.  :ref:`glossaries`
