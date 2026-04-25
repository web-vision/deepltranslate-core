..  _editor-autotranslateprefix:

Auto-translate-prefix
=====================

To enable tagging of automatically translated pages and content, the page turned
on of translated pages has been extended to implement a control.


..  figure:: /Images/Editor/AutoTranslatePrefix/page-translation-properties.png
    :alt: Page Property DeepL Translate

Each time content is translated, the fields are updated.

----

The field information "Last translation date" and "DeepL Translated content has not been checked"
are always transferred to the page object and can be queried in Fluid.

In this way, information and notes can be controlled in the Fluid template if required.
This must be added to the template by a TYPO3 administrator or developer.

..  figure:: /Images/Editor/AutoTranslatePrefix/page-translation-prefix.png
    :alt: Page Frontend prefix

When an editor is previewing a hidden page translated by DeepL, a DeepL badge is
displayed in addition to the "Preview" badge in the upper right corner.

..  figure:: /Images/Editor/AutoTranslatePrefix/page-translation-preview.png
    :alt: Page frontend preview
