..  _bugfix-inline-children-are-translated-from-their-source-language-1791883528:

=================================================================
Bugfix: Inline children are translated from their source language
=================================================================


Description
===========

When a record with inline children was translated with DeepL, the source
language of each field was taken from the record the translation was
started for. That record is kept for the whole request, and a
`deepltranslate` command issued while the record is still being localized
replaces it with the record of the command.
`web-vision/deepltranslate-auto-renew` issues such a command for the first
inline child translation saved during the localization.

From then on, the fields of all further inline children, nested ones
included, were sent to DeepL with the target language as source language,
a German text as English to English (UK) for example.
DeepL returned them unchanged, so only the fields localized before were
translated, and the characters were billed anyway.

Impact
======

Every field is translated from the language of the record it belongs to.
Records with nested inline children, a study plan with semesters and
modules for example, are translated completely. A record of all
languages, an inline child for example, is translated from the default
language.

See pull request `#693
<https://github.com/web-vision/deepltranslate-core/pull/693>`__.
