..  _basic-usage:

=====================
Basic Usage TYPO3 v14
=====================

Once the extension is installed and the API key provided, we are ready to start
translating pages, content elements and/or records.

*   `Translate Page with or without contents elements <translatePageWithOrWithoutContents>`_
*   `Translate Content Elements <translateContentElements>`_
*   `Translate Record <translateRecord>`_

..  note::

    TYPO3 v14 revamped the overall localization process looking different and
    also working in a different way then in TYPO3 v13.

    * See `TYPO3 v13 Basic Usage <basicUsageTYPO3v13>`_ if you are on TYPO3 v13.

..  _translatePageWithOrWithoutContents:

Translate Page with or without contents elements
================================================

:guilabel:`deepltranslate-core` respects the new translation workflow and
shares the same wizard provided by TYPO3 v14, which means that translating
a page and optional directly the contents are started using the generic
translation dropdown.

..  rst-class:: bignums-tip

#.  Generic translation dropdown to create translation

    ..  figure:: /Images/Editor/page-translation-dropdown-create-translation.png
        :alt: Generic translation dropdown to create translation

#.  Select content elements to translate

    ..  figure:: /Images/Editor/page-translation-wizard-content-selection.png
        :alt: Select content elements to translate

#.  Select :guilabel:`Translate` localization mode

    ..  figure:: /Images/Editor/page-translation-wizard-select-localization-mode.png
        :alt: Select `Translate` localization mode

    ..  note::

        DeepL based translation is only available for :guilabel:`Translate`
        localization mode and will not be available if :guilabel:`Copy`
        localization mode is selected here.

#.  Select `Translate with DeepL` localization handler

    ..  figure:: /Images/Editor/page-translation-wizard-select-translate-localization-handler.png
        :alt: Select `Translate with DeepL` localization handler

#.  Review and confirm selected localization options

    ..  figure:: /Images/Editor/page-translation-wizard-translation-confirmation.png
        :alt: Review and confirm selected localization options

#.  Localization is in progress

    ..  figure:: /Images/Editor/page-translation-wizard-processingdata.png
        :alt: Localization is in progress

#.  Localization completed successfully

    ..  figure:: /Images/Editor/page-translation-wizard-localization-completed.png
        :alt: Localization completed successfully

..  _translateContentElements:

Translate Content Elements
==========================

Once the extension is installed and the API key provided, we are ready to start
translating content elements. When translating a content element, there are four
additional options besides the normal translate and copy.

* DeepL Translate (auto detect).
* DeepL Translate.

..  rst-class:: bignums-tip

#.  Start translate of missing records for language

    ..  figure:: /Images/Editor/content-translation-translate-button.png
        :alt: Start translate of missing records for language

#.  Select source language

    ..  figure:: /Images/Editor/content-translation-wizard-select-source-language.png
        :alt: Select source language

        In case content element translation for current page already exists TYPO3
        allows to select the source language to translate content elements from.

        ..  note::

            This step is not shown in case no translated content exists**

#.  Select content elements to translate

    ..  figure:: /Images/Editor/content-translation-wizard-select-content-elements-to-translate.png
        :alt: Select content elements to translate

#.  Select :guilabel:`Translate` localization mode

    ..  figure:: /Images/Editor/content-translation-wizard-select-localization-mode.png
        :alt: Select Translate localization mode

        ..  note::

            DeepL based translation is only available for :guilabel:`Translate`
            localization mode and will not be available if :guilabel:`Copy`
            localization mode is selected here.

#.  Select `Translate with DeepL` localization handler

    ..  figure:: /Images/Editor/content-translation-wizard-select-translate-localization-handler.png
        :alt: Select `Translate with DeepL` localization handler

#.  Review and confirm selected localization options

    ..  figure:: /Images/Editor/content-translation-wizard-translation-confirmation.png
        :alt: Review and confirm selected localization options

#.  Localization is in progress

    ..  figure:: /Images/Editor/content-translation-wizard-processingdata.png
        :alt: Localization is in progress

#.  Localization completed successfully

    ..  figure:: /Images/Editor/content-translation-wizard-localization-completed.png
        :alt: Localization completed successfully

.. _translateRecord:

Translate Record
================

In list view, you are able to translate single elements by clicking the DeepL
translate button for the language you want.

..  note::

    In TYPO3 v13 extra `localize to` action are provided and not available in
    case no `DeepL` translation configured for a language. In TYPO3 v14 the
    action is always available and in case the selected language does not have
    a valid `DeepL` translation the `Translate with DeepL` localization handler
    will not show up.

.. attention::

    Fields of custom extensions need to be properly
    :ref:`configured in TCA <tableConfiguration>` to enable translation.

..  rst-class:: bignums-tip

#.  Start localization for a record with :guilabel:`Localize to` in the :guilabel:`Records Module`

    ..  figure:: /Images/Editor/record-translation-localizetobutton.png
        :alt: Start localization for a record with `Localize to` in the `Records Module`

..  note::

    The process is the same as already visually demonstrated above. Dedicated
    images will be added in the next extension release.


