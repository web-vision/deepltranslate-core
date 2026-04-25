..  _extensionConfiguration:

=======================
Extension Configuration
=======================

Some general settings can be configured in the Extension Configuration.

..  rst-class:: bignums-tip

#.  Go to :guilabel:`System > Settings > Extension Configuration`
#.  Choose :guilabel:`deepltranslate_core`

The settings are divided into several tabs and described here in detail:

..  contents:: Properties
    :local:
    :depth: 2


Settings
========

Tab :guilabel:`Settings` provides basic extension settings required for minimal
operational state.

..  figure:: /Images/Reference/configuration-settings.png
    :alt: Screenshot of :guilabel:`Extension Configuration > deepltranslate_core > Settings`

..  confval-menu::
    :name: settings
    :display: table
    :type:
    :default:
    :exclude: modelType, splitSentences, preserveFormatting, ignoreTags, nonSplittingTags, splittingTags, outlineDetection

..  _deeplApiKey:
..  confval:: apiKey
    :type: string
    :required: true

    Add your DeepL API Key here, either `DeepL Free API`_ or `DeepL Pro`_.

..  _DeepL Free API: https://www.deepl.com/pro-checkout/account?productId=1200&yearly=false&trial=false
..  _DeepL Pro: https://www.deepl.com/de/pro

API
===

Tab :guilabel:`API` provides the ability to set default settings used for DeepL API.
requests.

..  note::

    Options have been added in preparation for upcoming :guilabel:`Glossary API v3`
    usage in :guilabel:`deepltranslate_glossary` and also in :guilabel:`deepltranslate_core`,
    but have not been implemented yet. They are not used yet.

..  figure:: /Images/Reference/configuration-api.png
    :alt: Screenshot of :guilabel:`Extension Configuration > deepltranslate_core > API`

..  confval-menu::
    :name: api
    :display: table
    :type:
    :default:
    :exclude: apiKey

..  confval:: modelType
    :type: string
    :default: `prefer_quality_optimized`: Prefer quality optimized (with fallback)

    DeepL model, available options:

    * `latency_optimized`: Latency optimized
    * `quality_optimized`: Quality optimized
    * `prefer_quality_optimized` Prefer quality optimized (with fallback)

..  confval:: splitSentences
    :type: string
    :default: `on`: Split at punctuation and newlines

    Enable Text splitting, available options:

    * `off`: No splitting
    * `on`: Split at punctuation and newlines
    * `nonewlines`: Split only at new lines

..  confval:: preserveFormatting
    :type: bool
    :default: `false`

    Keep existing formatting

..  confval:: ignoreTags
    :type: string

    Exclude content from being translated in between these tags; Comma separated, useful for
    available options:

    * `placeholders`
    * `shortcodes`
    * `pre`

    For example: `shortcodes,pre`

..  confval:: nonSplittingTags
    :type: string

    Only for tag_handling XML! Prevent splitting inside these tags. Comma
    separated, allows the API keeping the text unsplitted.

..  confval:: splittingTags
    :type: string

    Split inside these tags. Comma separated list, defines tags that should be
    splitted, for example: `p,div`

..  confval:: outlineDetection
    :type: bool
    :default: `true`

    Enable automatic detection of document structure in XML/HTML
