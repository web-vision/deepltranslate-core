..  _feature-translate-inline-records-with-deepl-1789729765:

============================================
Feature: Translate inline records with DeepL
============================================


Description
===========

Inline (IRRE) child records, for example the items of a card group content
element, get a :guilabel:`Translate with DeepL` control in the edit form of an
already translated parent record. The control is shown for each child which has
no translation in the language of the parent yet, next to the localize button
of TYPO3. It is an icon with the label as tooltip.

Such children are usually added in the default language after the parent has
been translated. Their tables are mostly hidden in the list module
(`ctrl.hideTable`), so the editor had no DeepL action for them so far.

In TYPO3 v13 the control asks for confirmation, translates the child with DeepL,
attaches the translation to the translated parent and opens the edit form
again. Unsaved changes of the form are discarded, which the confirmation points
out.

In TYPO3 v14 the control opens the localization wizard of TYPO3 for the child,
where the editor selects :guilabel:`Translate with DeepL` next to the
localization handlers of TYPO3. When the wizard is finished, the edit form of the translated parent is
opened. TYPO3 asks before unsaved changes of an open form are discarded.

The control is offered under the same conditions as the DeepL buttons of the
list module:

*   a DeepL API key is configured,
*   the child table is not excluded with
    :php-short:`\WebVision\Deepltranslate\Core\Event\DisallowTableFromDeeplTranslateEvent`,
*   the child table is not read only, and the backend user may modify it,
*   the backend user is an administrator or has the
    :ref:`Allowed Translate <administration-access>` permission, and has access
    to the language,
*   the language of the parent has a DeepL target language in the site
    configuration (`deeplTargetLanguage`).

In addition, the inline field has to show the untranslated children in the
translated parent (`appearance.showPossibleLocalizationRecords`), and the
localize button of TYPO3 has to be offered for the child, so
`appearance.enabledControls.localize` and listeners of
:php:`\TYPO3\CMS\Backend\Form\Event\ModifyInlineElementEnabledControlsEvent`
are respected, and the inline field has to point from the child to the parent
with `foreign_field`. Fields storing a list of child uids or using an `MM`
table do not get the control, because the translation could not be attached to
the translated parent. File references (`type => 'file'`) do not get it
either.

The localize button, :guilabel:`Localize all records` and
:guilabel:`Synchronize with original language` of TYPO3 keep their behaviour.
They copy the content of the child records.

For the localization wizard of TYPO3 v14, see
:ref:`important-manual-translation-of-inline-records-in-the-wizard-1790951104`.

Impact
======

Editors can translate newly added inline child records of a translated parent
with DeepL, without deleting and translating the whole parent again.

See issue `#558 <https://github.com/web-vision/deepltranslate-core/issues/558>`__.
