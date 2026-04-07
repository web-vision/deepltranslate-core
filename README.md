[![Latest Stable Version](https://poser.pugx.org/web-vision/deepltranslate-core/v/stable.svg?style=for-the-badge)](https://packagist.org/packages/web-vision/deepltranslate-core)
[![License](https://poser.pugx.org/web-vision/wv_deepltranslate/license?style=for-the-badge)](https://packagist.org/packages/web-vision/wv_deepltranslate)
[![TYPO3 14.2](https://img.shields.io/badge/TYPO3-14.2-green.svg?style=for-the-badge)](https://get.typo3.org/version/14.2)
[![TYPO3 13.4](https://img.shields.io/badge/TYPO3-13.4-green.svg?style=for-the-badge)](https://get.typo3.org/version/13.4)
[![TYPO3 12.4](https://img.shields.io/badge/TYPO3-12.4-green.svg?style=for-the-badge)](https://get.typo3.org/version/12.4)
[![Total Downloads (deepltranslate_core) >= 5.x](https://poser.pugx.org/web-vision/deepltranslate-core/downloads.svg?style=for-the-badge)](https://packagist.org/packages/web-vision/deepltranslate-core)
[![Monthly Downloads (deepltranslate_core) >= 5.x](https://poser.pugx.org/web-vision/deepltranslate-core/d/monthly?style=for-the-badge)](https://packagist.org/packages/web-vision/deepltranslate-core)
[![Total Downloads (wv_deepltranslate) <= 4.x](https://poser.pugx.org/web-vision/wv_deepltranslate/downloads.svg?style=for-the-badge)](https://packagist.org/packages/web-vision/wv_deepltranslate)
[![Monthly Downloads (wv_deepltranslate) <= 4.x](https://poser.pugx.org/web-vision/wv_deepltranslate/d/monthly?style=for-the-badge)](https://packagist.org/packages/web-vision/wv_deepltranslate)

# TYPO3 extension `deepltranslate_core`


|                  | URL                                                                 |
|------------------|---------------------------------------------------------------------|
| **Repository:**  | https://github.com/web-vision/deepltranslate-core                   |
| **Read online:** | https://docs.typo3.org/p/web-vision/deepltranslate-core/main/en-us/ |
| **TER:**         | https://extensions.typo3.org/extension/deepltranslate_core/         |
| **ISSUES:**      | https://github.com/web-vision/deepltranslate-core/issues/           |
| **RELEASES:**    | https://github.com/web-vision/deepltranslate-core/releases/         |

## Description

This extension provides automated translation of pages, content and records in TYPO3
for languages supported by [DeepL](https://www.deepl.com/de/docs-api/).

## Compatibility

| Branch | State          | Composer Package Name          | TYPO3 Extension Key | Version       | TYPO3     | PHP                                               |
|--------|----------------|--------------------------------|---------------------|---------------|-----------|---------------------------------------------------|
| main   | development    | web-vision/deepltranslate-core | deepltranslate_core | 6.0.x-dev     | v13 + v14 | 8.2, 8.3, 8.4, 8.5 (depending on TYPO3)           |
| 5      | active support | web-vision/deepltranslate-core | deepltranslate_core | ^5, 5.1.x-dev | v12 + v13 | 8.1, 8.2, 8.3, 8.4, 8.5 (depending on TYPO3)      |
| 4      | end of live    | web-vision/wv_deepltranslate   | wv_deepltranslate   | -             | -         | -                                                 |
| 3      | end of live    | web-vision/wv_deepltranslate   | wv_deepltranslate   | -             | -         | -                                                 |
| 2      | end of live    | web-vision/wv_deepltranslate   | wv_deepltranslate   | -             | -         | -                                                 |
| 1      | end of live    | web-vision/wv_deepltranslate   | wv_deepltranslate   | -             | -         | -                                                 |

## Features

* Translate content elements via TYPO3 built-in translation wizard
* Single drop down translation parallel to regular page translation
  * Translate your page with all fields you want
* One-Click translation of single records
* Glossary support
  * Manage your own glossaries in TYPO3
  * Synchronise glossaries to DeepL API
  * Translate content using your glossaries

![Screenshot](Documentation/Images/example-of-deepl-translation-selection-in-typo3-backend.png)

## Early-Access-Programm

Early access partners of DeepL Translate will benefit from exclusive access to all add-ons, developer preview versions, access to private GitHub repositories, priority support, logo placement and a backlink on the official website. You will also get access to the DeepL Translate version 5.0 announced for TYPO3 v13.

The following add-ons are currently available as part of the Early Access Program:

* **DeepL Translate Assets**: Translation of file meta data with DeepL
* **DeepL Translate Auto-Renew**: Automatic creation of pages and content elements in translations, renewal of translations when the original language changes
* **DeepL Translate Bulk**: Bulk translation of pages and content based on the page tree
* **Enable Translated Content**: Activation of all translated content elements with one click

Find out more: https://www.web-vision.de/en/deepl.html

## Installation

Install with your flavour:

* [TER](https://extensions.typo3.org/extension/deepltranslate_core/)
* Extension Manager
* composer

We prefer composer installation:

```bash
composer require 'web-vision/deepltranslate-core':'6.0.*@dev'
```

> [!IMPORTANT]
> `6.0.0` is still in development and not released yet and add-ons,
> public and private, are not touched yet and will be worked after
> having `EXT:deepltranslate_core` in a first usable state.

**Testing 6.0.0-dev version in projects (composer mode)**

> [!IMPORTANT]
> Currently none of the addons are touched yet and can be used with `6.0.0-dev`
> for TYPO3 v13 or TYPO3 v14, which we will start working on the next time.
> That means, that you need to remove them first before upgrading to `6.0.0-dev`.

It is already possible to use and test the `6.0.0-dev` version in composer based
instances, which is encouraged. We also encourage to give early feedback of issues
not detected by us or contributors.

Your project should configure `minimum-stabilty: dev` and `prefer-stable` to allow
requiring each extension but still use stable versions over development versions:

```bash
composer config minimum-stability "dev" \
&& composer config "prefer-stable" true
```

and remove possible addons:

```bash
composer require -W \
  'web-vision/deepl-write' \
  'web-vision/deepltranslate-glossary' \
  'web-vision/deepltranslate-assets' \
  'web-vision/deepltranslate-auto-renew' \
  'web-vision/deepltranslate-mass'
```

and installed with:

```bash
composer require -W \
  'web-vision/deepl-base':'2.0.*@dev' \
  'web-vision/deepltranslate-core':'6.0.*@dev' \
&& vendor/bin/typo3 extension:setup \
&& vendor/bin/typo3 language:update \
&& vendor/bin/typo3 cache:flush \
&& vendor/bin/typo3 cache:warmup
```

> [!NOTE]
> To avoid deprecation messages regarding deprecated `ext_emconf.php` you should
> disable logging deprecations or you need to apply a couple of composer patches
> against TYPO3 v14.2 (feature freeze).
> TYPO3 v14.3 (LTS) development currently not supported and possible needs more
> work and adoption depending what of the pending gerrit changes will be merged.

## Add-Ons

* [**DeepL Translate Glossary**](https://github.com/web-vision/deepltranslate-glossary):
  TYPO3-managed glossary for custom translation support

## Sponsors

We appreciate very much the sponsorships of the developments and features in
the DeepL Translate Extension for TYPO3.

### DeepL "Add automatic translation flag and hint" sponsored by

* [FH Aachen](https://www.fh-aachen.de/)

## Create a release (maintainers only)

Prerequisites:

* git binary
* ssh key allowed to push new branches to the repository
* GitHub command line tool `gh` installed and configured with user having permission to create pull requests.

**Prepare release locally**

> Set `RELEASE_BRANCH` to branch release should happen, for example: 'main'.
> Set `RELEASE_VERSION` to release version working on, for example: '5.0.0'.

```shell
echo '>> Prepare release pull-request' ; \
  RELEASE_BRANCH='main' ; \
  RELEASE_VERSION='6.0.0' ; \
  git checkout main && \
  git fetch --all && \
  git pull --rebase && \
  git checkout ${RELEASE_BRANCH} && \
  git pull --rebase && \
  git checkout -b prepare-release-${RELEASE_VERSION} && \
  composer require --dev "typo3/tailor" && \
  ./.Build/bin/tailor set-version ${RELEASE_VERSION} && \
  composer remove --dev "typo3/tailor" && \
  composer config "extra"."typo3/cms"."version" "${RELEASE_VERSION}" && \
  git add . && \
  git commit -m "[TASK] Prepare release ${RELEASE_VERSION}" && \
  git push --set-upstream origin prepare-release-${RELEASE_VERSION} && \
  gh pr create --fill-verbose --base ${RELEASE_BRANCH} --title "[TASK] Prepare release for ${RELEASE_VERSION} on ${RELEASE_BRANCH}" && \
  git checkout main && \
  git branch -D prepare-release-${RELEASE_VERSION}
```

Check pull-request and the pipeline run.

**Merge approved pull-request and push version tag**

> Set `RELEASE_PR_NUMBER` with the pull-request number of the preparation pull-request.
> Set `RELEASE_BRANCH` to branch release should happen, for example: 'main' (same as in previous step).
> Set `RELEASE_VERSION` to release version working on, for example: `0.1.4` (same as in previous step).

```shell
RELEASE_BRANCH='main' ; \
RELEASE_VERSION='6.0.0' ; \
RELEASE_PR_NUMBER='123' ; \
  git checkout main && \
  git fetch --all && \
  git pull --rebase && \
  gh pr checkout ${RELEASE_PR_NUMBER} && \
  gh pr merge -rd ${RELEASE_PR_NUMBER} && \
  git tag ${RELEASE_VERSION} && \
  git push --tags
```

This triggers the `on push tags` workflow (`publish.yml`) which creates the upload package,
creates the GitHub release and also uploads the release to the TYPO3 Extension Repository.

