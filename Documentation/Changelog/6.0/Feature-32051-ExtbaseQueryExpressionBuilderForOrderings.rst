..  _feature-automatic-registration-of-accessiteminterface-1777020217:

========================================================
Feature: Automatic registration of `AccessItemInterface`
========================================================


Description
===========

`EXT:deepltranslate_core` provides the :php-short:`\WebVision\Deepltranslate\Core\Access\AccessItemInterface`
to define access subtypes to `use as Custom Permission Options <https://docs.typo3.org/permalink/t3coreapi:custom-permissions>`_
grouped as `deepltranslate`.

These items are now automatically registered through the Symfony Dependency
Injection container by simply providing autoconfigured classes implementing
:php-short:`\WebVision\Deepltranslate\Core\Access\AccessItemInterface`.

No manual registration required anymore.

..  note::

    See also `deprecated AccessRegistry methods <deprecation-accessregistry-methods-1777019642>`_
    to deal with deprecated methods and avoid deprecation log entries.

Example
-------

Implement custom access class:

..  code-block:: php
    :caption: EXT:my_ext/Classes/Access/CustomAccess.php

    <?php

    namespace MyVendor\MyExt\Access;

    use WebVision\Deepltranslate\Core\Access\AccessItemInterface;

    final class CustomAccess implements AccessItemInterface
    {
        public function getIdentifier(): string
        {
            return 'customAccess';
        }

        public function getTitle(): string
        {
            return 'LLL:EXT:my_ext/Resources/Private/Language/locallang.xlf:be_groups.deepltranslate_access.items.customAccess.title';
        }

        public function getDescription(): string
        {
            return 'LLL:EXT:my_ext/Resources/Private/Language/locallang.xlf:be_groups.deepltranslate_access.items.customAccess.description';
        }

        public function getIconIdentifier(): string
        {
            return 'custom-access-logo';
        }
    }

Impact
======

Classes implementing :php-short:`\WebVision\Deepltranslate\Core\Access\AccessItemInterface`
defined in extensions are now automatically registered and possible provide any
access items which have not been registered manually before.

This can be mitigated by using :php:`#[Exclude]` attribute on the class or exclude
it from the autoconfigure load configuration in `Configuration/Services.yaml`.

