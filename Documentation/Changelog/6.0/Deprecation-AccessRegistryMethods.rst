..  include:: /Includes.rst.txt

..  _deprecation-accessregistry-methods-1777019642:

=====================================
Deprecation: `AccessRegistry` methods
=====================================

Description
===========

Following :php:`\WebVision\Deepltranslate\Core\Access\AccessRegistry` methods
has been deprecated:

*   :php-short:`addAccess()` because access items are gathered based on the
    :php-short:`\WebVision\Deepltranslate\Core\Access\AccessItemInterface`
    and added through the constructor by the DI container. Calling this method
    does not add anything and emits now a :php:`E_USER_DEPRECATED` error.

*   :php:`getAllAccess()` is now deprecated and triggers a :php:`E_USER_DEPRECATED`
    error. Use :php:`getItems()` instead.

*   :php:`getAccess()` is now deprecated and triggers a :php:`E_USER_DEPRECATED`
    error. Use :php:`get()` instead.

*   :php:`hasAccess()` is now deprecated and triggers a :php:`E_USER_DEPRECATED`
    error. Use `has()` instead.

Impact
======

Calling deprecated :php:`\WebVision\Deepltranslate\Core\Access\AccessRegistry`
methods triggers a :php:`E_USER_DEPRECATED` error.


Affected installations
======================

All installations that call one of the deprecated
:php:`\WebVision\Deepltranslate\Core\Access\AccessRegistry` methods triggers a
:php:`E_USER_DEPRECATED` error. Use the above mentioned replacements or remove
the `addAccess()` calls in `ext_localconf.php`.

Migration
=========

addAccess()
-----------

To register additional :php-short:`\WebVision\Deepltranslate\Core\Access\AccessItemInterface`
the approach have been to provide following code in `EXT:my_ext/ext_localconf.php`:

..  code-block:: php
    :caption: EXT:my_ext/ext_localconf.php

    <?php

    use TYPO3\CMS\Core\Utility\GeneralUtility;
    use WebVision\Deepltranslate\Core\Access\AccessRegistry;

    $accessRegistry = GeneralUtility::makeInstance(AccessRegistry::class);
    $accessRegistry->addAccess(new CustomAccessItem());

This is no longer required and does not do anything except triggering an
:php:`E_USER_DEPRECATED` error and can be simply removed.

In case dual extension version support is required encapsulate it into a
condition, for example:

..  code-block:: php
    :caption: EXT:my_ext/ext_localconf.php

    <?php

    use TYPO3\CMS\Core\Utility\GeneralUtility;
    use WebVision\Deepltranslate\Core\Access\AccessRegistry;
    use WebVision\Deepltranslate\Core\TranslatorInterface;

    // @todo web-vision/deepltranslate-core:>=6.0.0 Remove complete condition
    //       block once lowest supported deepltranslate-core extension version
    //       has been raised to 6.0.0 or higher.
    if (class_exists(TranslatorInterface::class) === false) {
        $accessRegistry = GeneralUtility::makeInstance(AccessRegistry::class);
        $accessRegistry->addAccess(new CustomAccessItem());
    }

getAllAccess()
--------------

:php:`getAllAccess()` can be replaced simply using the new `get()` method:

..  code-block:: diff
    :caption: EXT:my_ext/Classes/SomeClass.php

     <?php

     namespace MyVendor\MyExt;

     final class SomeClass {
         public function __construct(
             private AccessRegistry $accessRegistry,
         ) {}

         public function someMethod(): void
         {
    -       $allRegisteredAccessItems = $this->accessRegistry->getAllAccess();
    +       // `getItems()` returns now the identifiers as keys and `array_values()`
    +       // ensures to deal only with the items like with previous `getAllAccess()`.
    +       $allRegisteredAccessItems = array_values($this->accessRegistry->getItems());

             foreach($allRegisteredAccessItems as $accessItem) {
                 // ... do something with the access item
             }
         }
     }

hasAccess(), getAccess()
------------------------

Deprecated :php:`hasAccess()` and `getAccess()` can be simply replaced by their
new counter methods based on the now used :php:`\Psr\Container\ContainerInterface`:

..  code-block:: diff
    :caption: EXT:my_ext/Classes/SomeClass.php

     <?php

     namespace MyVendor\MyExt;

     final class SomeClass {
         public function __construct(
             private AccessRegistry $accessRegistry,
         ) {}

         public function hasAccess(): bool
         {
            $accessIdentifier = '';
    -       if (!$this->accessRegistry->hasAccess($accessIdentifier)) {
    +       if (!$this->accessRegistry->has($accessIdentifier)) {
              // access does not exists, take this as allowed.
              return true;
            }

    -       $accessItem = $this->accessRegistry->getAccess($accessIdentifier);
    +       $accessItem = $this->accessRegistry->get($accessIdentifier);
            return $this->getBackendUserAuthentication()->check(
                'custom_options',
                sprintf('deepltranslate:%s', $accessItem->getIdentifier()),
            );
         }

         private function getBackendUserAuthentication(): BackendUserAuthentication
         {
             return $GLOBALS['BE_USER'];
         }
     }

