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

..  _tableConfigurationContentBlocks:

Content Blocks
==============

Fields defined with `Content Blocks`_ follow the same rule. Content Blocks
sets no ``l10n_mode`` on its own, so DeepL does not translate a field of a
Content Block until its YAML definition sets ``l10n_mode: prefixLangTitle``.
A field without it is copied to the translation unchanged.

..  code-block:: yaml
    :caption: <extension_key>/ContentBlocks/ContentElements/teaser-boxes/config.yaml

    name: vendor/teaser-boxes
    fields:
      - identifier: header
        useExistingField: true
      - identifier: teaser
        type: Textarea
        l10n_mode: prefixLangTitle
      - identifier: boxes
        type: Collection
        labelField: title
        fields:
          - identifier: title
            type: Text
            l10n_mode: prefixLangTitle
          - identifier: text
            type: Textarea
            enableRichtext: true
            l10n_mode: prefixLangTitle

*   Set ``l10n_mode: prefixLangTitle`` on every field of the types ``Text``
    and ``Textarea`` that DeepL should translate. Content Blocks writes it
    into the TCA column of the field, the same as the PHP override above.
*   The fields of a ``Collection`` need it as well. The records of a
    ``Collection`` are translated together with the record they belong to.
*   A field reused with ``useExistingField: true`` keeps the ``l10n_mode``
    of the existing column, Content Blocks ignores the option for it. TYPO3
    configures ``header`` and ``bodytext`` of ``tt_content`` with
    ``prefixLangTitle``, and :guilabel:`deepltranslate_core` adds
    ``subheader``. Any other existing field needs the PHP override above.

..  note::

    Content Blocks supports ``l10n_mode`` in this way since version 1.1.0.
    See the option `l10n_mode`_ in the documentation of Content Blocks.

..  _Content Blocks: https://docs.typo3.org/p/friendsoftypo3/content-blocks/main/en-us/
..  _l10n_mode: https://docs.typo3.org/p/friendsoftypo3/content-blocks/main/en-us/YamlReference/FieldTypes/Index.html#confval-field-types-l10n-mode
