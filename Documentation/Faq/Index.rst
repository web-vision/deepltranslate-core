..  _faq:

FAQ
===

My dropdown in the site configuration is empty
----------------------------------------------

This happens if TYPO3 cached the request from the DeepL API for allowed languages,
but no API key was provided. In this case, empty your system cache in TYPO3.
Normally no cache files should be created when no API key is provided.

If this step does not work, delete the cached files manually. The location is as follows:


..  tabs::

    ..  group-tab:: composer mode installation

        `var/cache/data/deepltranslate_core`

    ..  group-tab:: classic mode installation

        `typo3temp/var/cache/data/deepltranslate_core`

After deleting the files in this directory and going to Site Configuration, the
extension will reload the cache and the dropdown should have all the translatable
language keys.

What will be the cost for DeepL API subscription?
-------------------------------------------------

You can find all the details regarding the usage of the DeepL API here:

*   https://www.deepl.com/pro-pricing.html
