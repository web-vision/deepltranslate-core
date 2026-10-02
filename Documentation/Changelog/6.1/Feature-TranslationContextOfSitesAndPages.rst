..  _feature-translation-context-of-sites-and-pages-1790976199:

===============================================
Feature: Translation context of sites and pages
===============================================


Description
===========

DeepL translates every field of a record on its own. A heading, a button label
or a teaser has no sentences around it, so DeepL has to guess what an
ambiguous word means: "The court is closed on Sundays." becomes a court of law
in German, also on the website of a tennis club.

The DeepL API takes a `context
<https://developers.deepl.com/docs/best-practices/working-with-context>`__
for this: text describing the content, which DeepL reads to translate it, but
does not translate. Its characters are not billed. A context can be configured
for a site and for a page, and is sent with every request of a translated
field, the texts of its `title`, `alt` and `aria-label` attributes included.

Site configuration
    :guilabel:`Translation context` (`deeplContext`) in the :guilabel:`DeepL`
    tab of the site. It is used for every page and record of the site.

    ..  code-block:: yaml
        :caption: config/sites/<identifier>/config.yaml

        deeplContext: 'The website of a tennis club in Berlin, for its members and guests. Members book tennis courts online.'

Page properties
    :guilabel:`Translation context` (`tx_wvdeepltranslate_context`) in the
    :guilabel:`DeepL Translate` tab of a page in the default language. It
    replaces the context of the site for the page itself and for the records
    on it. Subpages do not inherit it. The translations of the page keep the
    value of the default language and do not show the field. It is an
    excluded field, editors need the permission for it in their backend
    group.

The context of the page comes first, then the context of the site. A context
of whitespace only counts as none. Changed in a workspace, the context of the
page is used in the version of that workspace.

A few sentences about the subject and the audience are enough. They are sent as
they are written, also when a record is translated from another language than
the default one. The forms limit both fields to 3000 characters. DeepL sets no
limit of its own, but a request including its context must not exceed 128 KiB.

Translations started by other extensions through
:php-short:`\WebVision\Deepltranslate\Core\Service\DeeplService`, for example
by `web-vision/deepltranslate-auto-renew`, use the context of the page their
record is on as well. A record without a page, like the metadata of a file, is
translated without a context.

Event to change the context
---------------------------

The PSR-14 event
:php-short:`\WebVision\Deepltranslate\Core\Event\DeepLContextEvent` is
dispatched right before a field is sent to DeepL. Its property `context` holds
the context the request is sent with and can be changed. An empty string sends
the request without a context. The event carries the source language (`null`
when DeepL detects it), the target language and the page of the translated
record as :php-short:`\WebVision\Deepltranslate\Core\Domain\Dto\CurrentPage`
(`null` without one).

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/ShopContext.php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use WebVision\Deepltranslate\Core\Event\DeepLContextEvent;

    #[AsEventListener('my-extension/shop-context')]
    final readonly class ShopContext
    {
        public function __invoke(DeepLContextEvent $event): void
        {
            if ($event->currentPage?->uid === 42) {
                $event->context = trim($event->context . ' The shop sells bicycles and their parts.');
            }
        }
    }

A caller of :php:`DeeplService::translateContent()` sets a context with
:php:`TranslateContext::setContext()`. The page and the site are not read then,
the event is dispatched with that context.

Impact
======

Integrators describe a site or a page in a few sentences, and DeepL translates
ambiguous words of short fields in their meaning on that site. Without a
context, requests are sent as before.

The page field is a new database column, update the database schema after the
update, for example with `vendor/bin/typo3 extension:setup`.

The translator of the extension implements the new
:php:`\WebVision\Deepltranslate\Core\ContextAwareTranslatorInterface`, which
extends :php:`\WebVision\Deepltranslate\Core\TranslatorInterface` by the
argument :php:`string $context = ''` of :php:`translate()`. A custom translator
implementing only :php:`TranslatorInterface` keeps working and is called
without a context, implement the new interface to receive it.

See issue `#666 <https://github.com/web-vision/deepltranslate-core/issues/666>`__.
