..  _bugfix-languages-of-the-deepl-dropdown-for-editors-1791280857:

=====================================================================
Bugfix: The DeepL dropdown offers editors the languages they may edit
=====================================================================


Description
===========

In TYPO3 v13 the list module shows a dropdown to create a page translation
with DeepL. For a backend user without admin rights, the check of the
language permission was inverted: the dropdown skipped every language the
user is allowed to edit and offered only those the user is not allowed to
edit.

An editor whose groups allow all languages, which is the case when no
language is selected in "Limit to languages", got no dropdown at all. An
editor restricted to some languages was offered exactly the other ones,
and creating such a translation failed on the missing permission.

Administrators were not affected. The check came with 5.1.4 and 6.0.0.

Impact
======

The dropdown offers a backend user without admin rights the languages
the user is allowed to edit, provided the user has the permission to
translate with DeepL.

See pull request `#690
<https://github.com/web-vision/deepltranslate-core/pull/690>`__.
