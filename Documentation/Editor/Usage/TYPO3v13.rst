..  _basicUsageTYPO3v13:

=====================
Basic Usage TYPO3 v13
=====================

Translating a page
==================

:guilabel:`deepltranslate-core` adds a separate dropdown for DeepL translation
of a page to the list and web module. The dropdown is filtered to not translated
pages and against DeepL API possible translation languages.

..  figure:: /Images/Editor/translation-dropdown.png
    :alt: Dropdown for DeepL translation

Translating content elements
============================

Once the extension is installed and the API key provided, we are ready to start
translating content elements. When translating a content element, there are four
additional options besides the normal translate and copy.

* DeepL Translate.

..  figure:: /Images/Editor/deepl-localization-mode.png
    :height: 450px
    :alt: DeepL Options

    DeepL translate options


Translating a single element
============================

In list view, you are able to translate single elements by clicking the DeepL
translate button for the language you want.

..  figure:: /Images/Editor/translation-buttons-page.png
    :alt: Translation buttons in list view

Languages that are not available will have no DeepL button. In this case,
use normal translation.

..  figure:: /Images/Editor/translation-button-news.png
    :alt: Translation button for tx_news, one language not available in DeepL

.. note::
   Fields of custom extensions need to be properly
   :ref:`configured <tableConfiguration>` to enable translation.
