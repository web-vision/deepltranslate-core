..  _feature-localize-inline-records-with-deepl-1789729765:

===========================================
Feature: Localize inline records with DeepL
===========================================


Description
===========

Inline (IRRE) child records, for example the items of a card group content
element, get a :guilabel:`Localize with DeepL` control in the edit form of an
already translated parent record. The control is shown for each child which has
no translation in the language of the parent yet, next to the localize control
of TYPO3.

Such children are usually added in the default language after the parent has
been translated. Their tables are mostly hidden in the list module
(`ctrl.hideTable`), so the editor had no DeepL action for them so far.

In TYPO3 v13 the control asks for confirmation, translates the child with DeepL,
attaches the translation to the translated parent and reloads the edit form.
Unsaved changes of the form are discarded, which the confirmation points out.

In TYPO3 v14 the control opens the localization wizard of TYPO3 for the child,
offering :guilabel:`Translate with DeepL` next to the localization handlers of
TYPO3. The editor stays in the edit form of the parent afterwards, TYPO3 asks
before unsaved changes are discarded.

The control is offered under the same conditions as the DeepL buttons of the
list module:

*   a DeepL API key is configured,
*   the child table is not excluded with
    :php-short:`\WebVision\Deepltranslate\Core\Event\DisallowTableFromDeeplTranslateEvent`,
*   the backend user is an administrator or has the
    :ref:`Allowed Translate <administration-access>` permission, and has access
    to the language,
*   the site configures DeepL for the source language and the language of the
    parent (:guilabel:`deeplTargetLanguage`).

The localize, :guilabel:`Localize all records` and
:guilabel:`Synchronize with default language` controls of TYPO3 keep their
behaviour. They copy the content of the child records.

In the localization wizard of TYPO3 v14, both :guilabel:`Translate with DeepL`
and the :guilabel:`Manual` handler of TYPO3 now create the translation of an
inline child in connected mode through the translated parent, so it is attached
to that parent, and keep the editor in the parent's edit form.
:guilabel:`Manual` still copies the content. A free mode copy is not changed.

Impact
======

Editors can translate newly added inline child records of a translated parent
with DeepL, without deleting and translating the whole parent again.
