..  _basic-usage:

=====================
Basic Usage TYPO3 v14
=====================

Once the extension is installed and the API key provided, we are ready to start
translating pages, content elements and/or records.

*   `Translate Page with or without contents elements <translatePageWithOrWithoutContents>`_
*   `Translate Content Elements <translateContentElements>`_
*   `Translate Record <translateRecord>`_
*   :ref:`Translate inline records <translateInlineRecords>`

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


..  _translateInlineRecords:

Translate inline records
========================

Inline records are records edited inside another record, for example the items
of a card group or an accordion. Their translation belongs to the translation of
the record they are part of, so they are translated together with it.

When an inline record is added in the default language after the record it
belongs to has already been translated, open the translated record. The new
inline record is listed there as not yet localized, with a DeepL icon in its
controls. Its tooltip says :guilabel:`Translate with DeepL`.

..  rst-class:: bignums-tip

#.  Click the DeepL icon :guilabel:`Translate with DeepL` in the controls of the
    inline record.

    ..  figure:: /Images/Editor/inline-record-translate-with-deepl-control.png
        :alt: The DeepL icon in the controls of an inline record which is not translated yet

#.  The localization wizard of TYPO3 opens for the inline record. Keep the mode
    :guilabel:`Translate` and click :guilabel:`Next`.

#.  :guilabel:`Manual Translation` is preselected as handler. Select
    :guilabel:`Translate with DeepL` instead and click :guilabel:`Next`.

    ..  figure:: /Images/Editor/inline-record-translation-wizard-select-handler.png
        :alt: The handler step of the localization wizard with Manual Translation preselected

#.  Check the summary and click :guilabel:`Localize`.

#.  When the wizard reports :guilabel:`Localization completed`, click
    :guilabel:`Finish`. The edit form of the translated record opens again and
    shows the translated inline record. If the form has unsaved changes, TYPO3
    asks whether to save or discard them first.

In TYPO3 v13 the icon asks for confirmation and translates the inline record
directly, see :ref:`basicUsageTYPO3v13`.

The icon is only shown when

*   the inline field shows records which are not translated yet, an integrator
    setting (ask your integrator if new inline records do not appear in the
    translated record),
*   DeepL is configured for the language of the translated record, and you are
    allowed to translate with DeepL and to edit the inline records,
*   the localize button of TYPO3 is shown for the inline record, and
*   the inline record is stored with a reference to the record it belongs to,
    which is the usual configuration. Inline fields that store a list of
    records do not get the icon, and neither do files (images, media).

..  note::

    The localize buttons of TYPO3 itself, including
    :guilabel:`Localize all records` and
    :guilabel:`Synchronize with original language`, copy the content without
    translating it.


