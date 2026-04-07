..  include:: /Includes.rst.txt

..  _installation:

Installation
============

The extension has to be installed like any other TYPO3 CMS extension.
You can download the extension using one of the following methods:

#.  **Use composer**:
    Run

    ..  code-block:: bash

        composer require 'web-vision/deepltranslate-core':'6.0.*@dev'

    in your TYPO3 installation.

#.  **Get it from the Extension Manager**:
    Switch to the module :guilabel:`Admin Tools > Extensions`.
    Switch to :guilabel:`Get Extensions` and search for the extension key
    *deepltranslate_core* and import the extension from the repository.

#.  **Get it from typo3.org**:
    You can always get current version from `TER`_ by downloading the zip
    version. Upload the file afterwards in the Extension Manager.

..  note::

    No release yet and you possible need to allow the installation of
    development versions:

    * :shell:`composer config minimum-stability "dev"`
    * :shell:`composer config "prefer-stable" true`

    Currently you need to uninstall any of the addons, private and public,
    because they are not compatible with `6.0.0-dev` or `TYPO3 v14` yet.

The extension then needs to be :ref:`configured <configuration>`
in order to display translation buttons in the desired languages.

..  _TER: https://extensions.typo3.org/extension/deepltranslate_core

Compatibility
-------------

DeepL Translate supports:

..  csv-table:: Changes
    :header: "DeepL Translate version","TYPO3 Version","PHP version","Supported","Composer","TER"
    :file: Files/versionSupport.csv
