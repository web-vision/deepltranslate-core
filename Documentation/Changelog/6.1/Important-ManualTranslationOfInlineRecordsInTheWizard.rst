..  _important-manual-translation-of-inline-records-in-the-wizard-1790951104:

=================================================================
Important: Inline records in the localization wizard of TYPO3 v14
=================================================================


Description
===========

In TYPO3 v14 the localization wizard is opened by the localize actions of the
list module and of the page module, and by the
:guilabel:`Translate with DeepL` control of inline records. The extension
changes how the wizard localizes an inline (IRRE) child record in connected
mode, meaning a child pointing to its parent with `foreign_field`. This applies
to every table, also when DeepL is not used.

:guilabel:`Manual Translation` of TYPO3
    Core's handler localizes such a child on its own. The translation then
    points to the default language parent, or to no parent at all, and is not
    shown in the edit form of the translated parent. The extension decorates the handler: in the mode
    :guilabel:`Translate` the child is localized through the translated parent,
    the way the localize button of the inline field does it, so it is attached
    to that parent. The content is still copied, not translated.

    *   Without a translated parent, the wizard reports an error and nothing is
        created. Core created the misplaced translation before.
    *   When the translation could not be created, for example because the only
        record of the parent in the language is a free mode copy, the wizard
        reports an error instead of success.
    *   The mode :guilabel:`Copy`, pages and records which are not inline
        children are handled by core unchanged.

:guilabel:`Translate with DeepL`
    The handler localizes such a child through its translated parent since
    6.0.4 already. It now reports the same errors as above.

Both handlers
    After an inline child has been localized, the wizard opens the edit form of
    the translated parent, the top-most one for nested inline records, instead
    of the edit form of the new translation. TYPO3 asks before unsaved changes
    of an open form are discarded.

The decorated handler keeps the identifier `manual`, its label and its place
as the first, preselected handler of the wizard. The class name passed to
listeners of
:php:`\TYPO3\CMS\Backend\Localization\Event\ModifyLocalizationHandlerIsAvailableEvent`
is the one of the decorator,
:php:`\WebVision\Deepltranslate\Core\Core14\Backend\Localization\InlineChildAwareManualLocalizationHandler`.

Impact
======

A listener which recognises core's handler by its class name,
:php:`\TYPO3\CMS\Backend\Localization\ManualLocalizationHandler::class`, does
not match any more and has to compare the identifier `manual` instead. A
service which type hints this internal class of core gets the decorator, which
implements
:php:`\TYPO3\CMS\Backend\Localization\LocalizationHandlerInterface` only.

Inline children localized with :guilabel:`Manual Translation` are attached to
the translated parent and shown in its edit form.
