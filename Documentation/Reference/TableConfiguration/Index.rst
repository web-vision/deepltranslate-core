..  _tableConfiguration:

===================
Table Configuration
===================

*deepltranslate_core* supports the translation of specific fields of TCA records.
It only understands fields to be translated only if their ``l10n_mode``
is set to ``prefixLangTitle``.

..  attention::

    :guilabel:`deepltranslate_core` only translates fields defined as TCA type
    `input` or `text`. Other fields cannot currently be translated automatically
    due to limitations in the DataHandler.

:guilabel:`deepltranslate_core` uses a :guilabel:`DataHandler hook` to detect
translatable fields.

The following setup is required to make :guilabel:`deepltranslate_core` work
on your table:

..  code-block:: php
    :caption: <extension_key>/Configuration/TCA/Overrides/<table_name>.php

    <?php

    $GLOBALS['TCA']['<table_name>']['columns']['<field_name>']['l10n_mode']
        = 'prefixLangTitle';
    $GLOBALS['TCA']['<table_name>']['columns']['<another_field_name>']['l10n_mode']
        = 'prefixLangTitle';
