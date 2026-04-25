.. _quickInstallation:

==================
Quick installation
==================

Composer mode
-------------

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

..  note::

    **Be aware** that aforementioned version constraints may be outdated, look
    up actual version by checking the `packagist.org <https://packagist.org/>`_
    meta-data repository.

    * `packagist.org - web-vision/deepltranslate-core<https://packagist.org/packages/web-vision/deepltranslate-core>`_
    * `packagist.org - web-vision/deepl-base <https://packagist.org/packages/web-vision/deepl-base>`_
    * `packagist.org - web-vision/deeplcom-deepl-php<https://packagist.org/packages/web-vision/deeplcom-deepl-php>`_

Classic mode
------------

