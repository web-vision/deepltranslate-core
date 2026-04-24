.. upgrade5to6:

==================
Upgrade 5.x to 6.x
==================

From an API perspective there are no relevant changes or any migrations
required (yet), which means you can simply update the extension.

See `changelog-v6`_ for details on included changes.

composer-mode
=============

..  note::

    Development versions needs to be allowed, which is done with the two
    composer config commands. With these, development version will be used
    unless `6.0.0` has been released. Afterwards only stable version will
    be picked up.

    On top, version is pinned to `6.0.x` on the patch-level, which helps
    with possible database changes or similar in-between changes requiring
    migrations, for example in case `DeepL` forces us to do this to keep up
    with API changes.

..  code-block:: bash
    composer config minimum-stability "dev" \
    && composer config "prefer-stable" true \
    composer require -W \
       "web-vision/deepltranslate-core":"6.0.*@dev"

classic-mode
=============

#.  **Get it from the Extension Manager**:
    Switch to the module :guilabel:`System > Extensions`.
    Switch to :guilabel:`Get Extensions` and search for the extension key
    *deepltranslate_core* and import the extension from the repository.

#.  **Get it from typo3.org**:
    You can always get current version from `TER`_ by downloading the zip
    version. Upload the file afterwards in the Extension Manager.

#.  **Get it from GitHub release**:
    TER upload archives are added to the corresponding GitHub release page,
    in case you need to download or update the extension and `GITHUB_RELEASES`_
    is down or not reachable.


..  _TER: https://extensions.typo3.org/extension/deepltranslate_core
..  _GITHUB_RELEASES: https://github.com/web-vision/deepltranslate-core/releases/
