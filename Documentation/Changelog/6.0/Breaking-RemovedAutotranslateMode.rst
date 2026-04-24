..  _breaking-removed-autotranslate-mode-1775514670:

======================================
Breaking: Removed `Autotranslate Mode`
======================================

Description
===========

The `autotranslate` mode has been removed now because it literally was only
a duplicate of the normal `translate` mode and was not handled differently.

Not having a use-case and a real difference possible at the moment the mode
is removed before re-implementing it for TYPO3 v14 again having no value.

In case a valid use-case and different handling purpose may raise up in the
future it will be (re-)added as a feature again.

Impact
======

Except not having it selectable anymore there is no impact because it was
the same process as the casual deepl based translate mode.

Affected installations
======================

Instances upgrading from `deepltranslate-core 5.x or earlier` will notice
that the mode has been dropped. It's simple not selectable anymore.

Migration
=========

There is no migration path. Developers can use the events to introduce a
custom mode again and handle it on their own if they really want or have
a change not provided upstream to make use of this mode.

Otherwise simple use the remaining deepl base translate mode and having
the same outcome.
