[![Latest Stable Version](https://poser.pugx.org/web-vision/wv_deepltranslate/v/stable.svg)](https://packagist.org/packages/web-vision/wv_deepltranslate)
[![TYPO3 11.5](https://img.shields.io/badge/TYPO3-11.5-orange.svg?style=flat-square)](https://get.typo3.org/version/11)
[![TYPO3 10.4](https://img.shields.io/badge/TYPO3-10.4-orange.svg?style=flat-square)](https://get.typo3.org/version/10)
[![TYPO3 9.5](https://img.shields.io/badge/TYPO3-9.5-orange.svg?style=flat-square)](https://get.typo3.org/version/9)
[![Total Downloads](https://poser.pugx.org/web-vision/wv_deepltranslate/downloads.svg)](https://packagist.org/packages/web-vision/wv_deepltranslate)
[![Monthly Downloads](https://poser.pugx.org/web-vision/wv_deepltranslate/d/monthly)](https://packagist.org/packages/web-vision/wv_deepltranslate)

## What does it do?

Fork of deepltranslate from pitsolutions. This extension provides option to translate content elements and tca record fields to desired language(supported by [Deepl](https://www.deepl.com/en/api.html)).
As a fallback, Google Translate option is also provided as they provide support for many languages that deepl isn’t providing.
For both Deepl translate and Google Translate, there are two modes-normal and autodetect, where the later autodetect source language and translates it to the desired language.

## Installation

You can install the extension using:

- Extension manager
- or composer

```bash
composer req web-vision/wv_deepltranslate
```

Once installed ,there appears a Deepl back end module with a settings tab.

## Supported TYPO3 Version

- TYPO3 8.5 to 8.7.99 (Tag v1.0.0 - 1.0.1)
- TYPO3 9.5.1 to 10.4.99 (Tag v1.0.2 onwards)
- TYPO3 11.5.1 to 11.5.99 (Tag v2.0.1 onwards)

## Extension Configuration

Once you installed the extension, you have to set the Deepl API Key under extension configuration section

## Translating Content Elements

Once the extension is installed and Api key provided we are good to go for translating content elements.
On translating content element,There appears additional four options apart from normal tranlate and copy.

- Deepl Translate(auto detect).
- Deepl Translate.
- Google Translate(auto detect).
- Google Translate.

## Translating TCA Records

Deepltranslate supports translation of specific fields of TCA records.It understands fields which need to be translated,
only if their `l10n_mode` is set to `prefixLangTitle`. For example if you need translation of fields of tx_news (teaser and bodytext),
You need to override those fields like follows:

Add it to TCA/Overrides:

```bash
example-extension/Configuration/TCA/Overrides/tx_news_domain_model_news.php
```

```php
<?php

if (!defined('TYPO3_MODE')) {
    die();
}

$GLOBALS['TCA']['tx_news_domain_model_news']['columns']['bodytext']['l10n_mode'] = 'prefixLangTitle';
$GLOBALS['TCA']['tx_news_domain_model_news']['columns']['teaser']['l10n_mode'] = 'prefixLangTitle';
```

## Translating Content Elements and TCA Records - Editor users

For the perfect working of deepltranslate with editor users , we need to make sure that the editor has some necessary permissions in `Access Lists`.
Make sure editors have the following permissions:

* Tables (modify) - Better provide permission to all core tables and necessary third party extension tables.
* Allowed excludefields

1. Page Content - Atleast provide permissions to `Columns (colPos)`, `Language (sys_language_uid)` and `Transl.Orig (l18n_parent)`

![GitHub Logo](./Documentation/Images/UserManual/page-content.png)

### Page Content

2. Other Tca record fields

![GitHub Logo](./Documentation/Images/UserManual/tca-fields.png)

### Other TCA Fields

> Explicitly allow/deny field values

1. Page Content: Type - Allow all to use all CE types.

![GitHub Logo](./Documentation/Images/UserManual/ce-types.png)

### CE types

## Deepl Module Settings

The settings module helps to assign the sytem languages to either deepl supported languages or to Google supported languages.
For example, you can assign German under Austrian German sys language if you wish.
For assigning a language to a sys language you must enter it’s isocode(ISO 639-1).

## FAQ

See faq [here](https://docs.typo3.org/typo3cms/extensions/wv_deepltranslate/Faq/Index.html)
