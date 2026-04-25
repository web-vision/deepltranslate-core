..  _administration-access:

====================
Access Configuration
====================

Access to the automatic translation functions with Deepl-Translate in the TYPO3 backend
can be defined via the following options in the user group settings.

..  figure:: /Images/Administration/BackendGroupAccess.png
    :alt: Backend Gruppen Access Right - Custom module options

..  note::

    In order to be able to use the backend group authorisations, an update to the latest version
    of the :php:`deepltranslate_core` (:php:`web-vision/deepltranslate-core`).

..  confval:: Allowed Translate

    This setting controls the visibility of the general translation function in the
    translation modal of the page module, in the translation options of data records in the list module
    and in the translation selection in the page header of the page and list module.

..  confval:: Allowed Glossary Sync

    This setting allows backend users of a backend user group with corresponding authorisation to
    synchronise glossary entries of a glossary SysFolder (SysFolder with activated glossary module) towards Deepl.
