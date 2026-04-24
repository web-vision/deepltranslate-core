..  _administrationConfiguration:

Configuration
=============


..  _adminConfigurationSetUpApi:

Set up API
----------

..  attention::
    Before using the DeepL API, you need to get an API key from your `DeepL Profile`_.

..  rst-class:: bignums-tip

#.  Go to the :ref:`extension configuration <extensionConfiguration>`
    in :guilabel:`System > Settings > Extension Configuration`.

    ..  note::

        In TYPO3 v13 the path is :guilabel:`AdminTools > Settings > Extension Configuration`.

#.  Open the settings for :guilabel:`deepltranslate_core > Settings` and add your API key.

    ..  figure:: /Images/Reference/configuration-settings-deeplapikey.png
        :alt: The Extension configuration settings showing the input field for DeepL API key

    The correct DeepL API endpoint for free or pro plans is auto-detected the given
    API key format.

..  note::

    Extension configuration provides additional API settings, currently unused
    and prepared for upcoming integration.

**Further Readings**

* :ref:`Global extension configuration <extensionConfiguration>`

..  _sitesetup:

Set up translation language
---------------------------

..  rst-class:: bignums-tip

#.  Go to :guilabel:`Site Management > Sites` and edit your site configuration
#.  Switch to tab `Languages` and open your target

    ..  figure:: /Images/Administration/site-config-deepl-settings-empty.png
        :alt: Site settings for a TYPO3 language showing empty DeepL Target Language dropdown

#.  Go to :guilabel:`DeepL Settings` and set up your `Target Language (ISO Code)`

    .. figure:: /Images/Administration/site-config-selected-target.png
        :alt: Selected target now set to German

.. note::
    Although the drop-down list can also be set in the default language, there is
    no point in defining a target language for the source language.

Choice a Formality
------------------

The formality configuration has been moved from the extension configuration to the SiteConfig languages.
The Formality Select field is only displayed if the selected Target-Translate of DeepL is supported.

.. note::
    For TYPO3 projects with more than one page root and language there is an upgrade wizard,
    which migrates the global formality configuration in the language config.

    .. figure:: /Images/Administration/site-config-selected-target-formally.png
        :alt: Selected target now set to German wird default formality

The same option is available in the Select field as DeepL API Supported.

..  confval:: deeplFormality

    :type: string

    Sets whether the translated text should lean towards formal or informal language.
    Possible options:

    default
        The default setting. If formal or informal depends on the language

    less
        Less formal language. Will fail, if no formality support for language

    more
        More formal language. Will fail, if no formality support for language

    prefer_less
        Use less formal language, if possible, otherwise fallback to default

    prefer_more
        Use more formal language, if possible, otherwise fallback to default


Configure tables
----------------

If not set by default, you need to define the `l10n_mode` for the fields you
want to have translatable by `deepltranslate_core`.

See the :ref:`tableConfiguration<table configuration>` for details.

Detecting target language
-------------------------

The following chain tries to detect the language to translate into:

#.  Set up DeepL Translation language in SiteConfiguration
    * Target languages detected from DeepL will only appear
#.  Check hreflang against DeepL supported languages
    * Needed for detecting EN-GB, EN-US, PT-PT or PT-BR
#.  Fallback to Language ISO code

For currently allowed languages see the `DeepL conform language key`_. As this
extension retrieves available languages from the API, translations are restricted
to the languages listed in the official DeepL API documentation.

If none of these match against DeepL API, translation for this language
is disabled for usage within DeepL. Translation buttons and dropdowns
respect this setting.

..  _DeepL conform language key: https://developers.deepl.com/docs/api-reference/languages
..  _DeepL Profile: https://www.deepl.com/en/your-account/keys
