..  _installation:

Installation
============

The extension has to be installed like any other TYPO3 CMS extension.
You can download the extension using one of the following methods:

..  tabs::

    ..  group-tab:: composer

        ..  code-block:: bash
            :caption: only the extension itself

            composer require -W \
               'web-vision/deepltranslate-core':'~6.0.0@dev'

        ..  code-block:: bash
            :caption: requiring depending TYPO3 extensions along the way

            composer require \
                'web-vision/deeplcom-deepl-php':'~1.18.0@dev' \
                'web-vision/deepl-base':'~2.0.0@dev' \
                'web-vision/deepltranslate-core':'~6.0.0@dev'

        ..  tip::

            :guilabel:`~6.0.0@dev` is the recommended version constraint to use, which
            locks the installable version down on :guilabel:`minor level (6.0)` having
            :guilabel:`6.0.0` as lowest patchlevel version. :guilabel:`@dev` in general
            would allow to install a possible development version and automatically
            switch to the stable release in case :guilabel:`minimum-stability: "dev"`
            and :guilabel:`prefer-stable: true` is configured in the root
            :guilabel:`composer.json` file.

            :guilabel:`-W` automatically installs required transient dependencies, for
            example:

            *   :guilabel:`web-vision/deepl-base` and
            *   :guilabel:`web-vision/deeplcom-deepl-php`

        ..  note::

            **Be aware** that aforementioned version constraints may be outdated, look up
            actual version by checking the `packagist.org <https://packagist.org/>`_
            meta-data repository.

            * `packagist.org - web-vision/deepltranslate-core<https://packagist.org/packages/web-vision/deepltranslate-core>`_
            * `packagist.org - web-vision/deepl-base <https://packagist.org/packages/web-vision/deepl-base>`_
            * `packagist.org - web-vision/deeplcom-deepl-php<https://packagist.org/packages/web-vision/deeplcom-deepl-php>`_

    ..  group-tab:: Extension Manager

        #.  Switch to the module :guilabel:`System > Extensions`.
        #.  Switch to :guilabel:`Get Extensions`
        #.  Search for the extension key :guilabel:`deepltranslate_core`
        #.  Import the extension from the repository.

        ..  note::

            For TYPO3 v13 navigate :guilabel:`AdminTools > Extensions` to
            find the **Extension Manager**.

    ..  group-tab:: Upload ZIP (TER)

        #.  Get current version from `TER`_ by downloading the zip version.
            Alternatively, get the zip from the `Github Releases`_ page.
        #.  Switch to the module :guilabel:`System > Extensions`.
        #.  Enable upload :guilabel:`Upload Extension`
        #.  Select or drag extension ZIP archive and upload the file

..  attention::

    The extension then needs to be :ref:`configured <configuration>`
    in order to display translation buttons in the desired languages.

..  _TER: https://extensions.typo3.org/extension/deepltranslate_core
..  _Github Releases: https://github.com/web-vision/deepltranslate-core/releases

Compatibility
-------------

DeepL Translate (CORE) supports:

..  csv-table:: Changes
    :header: "DeepL Translate version","TYPO3 Version","PHP version","Supported","Composer","TER"
    :file: Files/versionSupport.csv
