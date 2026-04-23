..  include:: /Includes.rst.txt

..  _breaking-removed-deepltranslateviewhelper-1775413934:

============================================
Breaking: Removed `DeeplTranslateViewHelper`
============================================

Description
===========

The `DeeplTranslateViewHelper` has been marked as deprecated in `5.x`
and is no longer used within the deepltranslate extension ecosystem
and is now removed without any replacement.

Following class is removed:

* :php:`\WebVision\Deepltranslate\Core\ViewHelpers\DeeplTranslateViewHelper`

Impact
======

Error is thrown that ViewHelper cannot be found when used in custom extension
or project fluid templates.

Affected installations
======================

Instances using the ViewHelper in project or extension fluid templates.


Migration
=========

There is no direct replacement for the ViewHelper and extension or project
needs to implement their own implementation, for example by cloning the
removed ViewHelper into a local path extension.

Own usage has been replaced for TYPO3 v13 the depending `EXT:deepl_base`
overrides the templates and using a ViewHelper which dispatches the PSR-14
:php:`\WebVision\Deepl\Base\Event\ViewHelpers\ModifyInjectVariablesViewHelperEvent`,
which is not needed in TYPO3 v14 because of the completely overhaul of the
translation (localization) behaviour.
