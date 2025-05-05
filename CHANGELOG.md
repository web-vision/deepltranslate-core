# Changelog

### 5.0.0
* [!!!][TASK] Prepare development for next major version by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/377
* [BUGFIX] Refactor to ProcessingInstruction lost DeepL mode information by @calien666 in https://github.com/web-vision/deepltranslate-core/pull/381
* [TASK] Cleanup removed TYPO3 v11 support by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/383
* [TASK] Ensure failing `php-cs-fixer` within Github Action pipeline by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/384
* [TASK] Ensure `TestCase::setUp()` parent call chain in tests by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/386
* [TASK] Ensure to define all depending extension in functional tests by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/387
* [TASK] Upgrade to typo3/testing-framework 8.x by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/389
* [TASK] Improve local ddev setup handling - first round by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/391
* [TASK] Mitigate renamed doctrine exception class by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/392
* [TASK] Avoid obsolete `version` in `.ddev/docker-compose.deeplmockserver.yml` by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/393
* [TASK] Migrate upgrade wizards registration by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/394
* [TASK] Avoid using deprecated `SiteLanguage->getTwoLetterCode()` by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/396
* [BUGFIX] Ender preview translated flag information in TYPO3 v12 (5.x) by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/398
* [!!!][TASK] Remove obsolete `DeeplPreviewFlagGeneratePageHook` by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/399
* [TASK] Add regression test for issue #395 by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/400
* [TASK] Add `sbuerk/typo3-cms-styleguide-version-sync` development dependency by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/401
* [!!!][TASK] Extract glossary related functionality into new addon `webvision/deepltranslate-glossary` by @calien666 in https://github.com/web-vision/deepltranslate-core/pull/405
* [TASK] Allow installation in TYPO3 v13 instances by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/407
* [TASK] Adjust tooling for v13 compatibility by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/408
* [TASK] Add table exclude event for DeepL translate buttons by @calien666 in https://github.com/web-vision/deepltranslate-core/pull/409
* [TASK] Use simplified backend module template for `UsageWidget` by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/410
* [!!!][TASK] Mitigate depecated flash message severity by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/411
* [TASK] Make functional tests executable for both core versions by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/412
* [!!!][TASK] Move glossary access registration into `EXT:deepltranslate_glossary` by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/413
* [TASK] Use custom DataHandler command instead of invalid cmdMap by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/414
* [TASK] Enable temporarly disabled GitHub Action checks by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/415
* [TASK] Update `deeplcom/deepl-php:^1.10.1` by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/416
* [!!!][TASK] Remove TYPO3 v11 backend JavaScript by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/418
* [TASK] Add glossary add-on section to README by @calien666 in https://github.com/web-vision/deepltranslate-core/pull/417
* [TASK] Redefine composer dependencies by @calien666 in https://github.com/web-vision/deepltranslate-core/pull/419
* [TASK] Ensure working PageLayout localization modal for v13 by @sbuerk in https://github.com/web-vision/deepltranslate-core/pull/420
* [TASK] Define classic mode autoloader in `ext_emconf.php` for depending and shipped packages by @calien666 in https://github.com/web-vision/deepltranslate-core/pull/421
* [TASK] Move glossary related documentation to `deepltranslate-glossary` by @calien666 in https://github.com/web-vision/deepltranslate-core/pull/422

> [!IMPORTANT]
> The package name and extension key has been renamed,
> see [Documentation/Administration/Updates/Index.rst](https://docs.typo3.org/p/web-vision/deepltranslate-core/main/en-us/Administration/Updates/Index.html).

### 4.4.0

* [BUGFIX] Detected current page right for pages by @Mabahe in https://github.com/web-vision/wv_deepltranslate/pull/329
* [BUGFIX] Streamline Glossary Command and fix some Bug by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/326
* [BUGFIX] Use TYPO3 configured proxy for DeepL client by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/331
* [BUGFIX] Mitigate unrelated page traversal for glossary lookup by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/335
* [BUGFIX] Ensure full compatible for proxy setting to retrieve Translator by @staempfli-webteam in https://github.com/web-vision/wv_deepltranslate/pull/336
* [TASK] Remove locallang overrides and tca description registry by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/339
* [TASK] Avoid implicitly nullable class method parameter by @sbuerk in https://github.com/web-vision/wv_deepltranslate/pull/345
* [TASK] Run pipelines in php 8.3 by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/347
* [TASK] Streamline tooling by @sbuerk in https://github.com/web-vision/wv_deepltranslate/pull/351
* [FEATURE] Refactor formality translation context handling. by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/353
* [TASK] Create deepl usage widget by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/338
* [TASK] Set the deepl-php requirement strict by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/363
* [BUGFIX] Conditionally add dashboard widget by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/362
* [TASK] Update documentation by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/348
* [FEATURE] Introduce translate access features for backend user groups by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/361
* [TASK] Replace `DeepL Mock Api Server` container image by @sbuerk in https://github.com/web-vision/wv_deepltranslate/pull/364
* [TASK] Fix badge and add more verbose links to needed configuration docs by @pixelbrackets in https://github.com/web-vision/wv_deepltranslate/pull/365
* [BUGFIX] Use TYPO3 http client by @Mabahe in https://github.com/web-vision/wv_deepltranslate/pull/372
* [TASK] Usage treshold & severity levels by @pixelbrackets in https://github.com/web-vision/wv_deepltranslate/pull/358
* [BUGFIX] Return buttons if page is not callable by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/374
* [BUGFIX] Working with glossary without a request not possible by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/375

### 4.3.1

* Resolve phpstan issues that block the pipeline by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/325
* Update README.md by @borishinzer in https://github.com/web-vision/wv_deepltranslate/pull/324

### 4.3.0

Public Release from EAP

### 4.2.1

* [BUGFIX] Fix loading issues with translation wizard in v11 by @NarkNiro in https://github.com/web-vision/wv_deepltranslate-eap/pull/67

### 4.2.0

* [FEATURE] Introduce Usage statistics by @calien666 in https://github.com/web-vision/wv_deepltranslate-eap/pull/45
* [TASK] Disable publish ci workflow by @NarkNiro in https://github.com/web-vision/wv_deepltranslate-eap/pull/55
* [BUGFIX] Loading the backend locallang exclusively in the backend by @NarkNiro in https://github.com/web-vision/wv_deepltranslate-eap/pull/53
* [TASK] Use the new php based documentation rendering container by @sbuerk in https://github.com/web-vision/wv_deepltranslate-eap/pull/57
* [TASK] Streamline command wrapper  `Build/Scripts/runTests.sh` by @sbuerk in https://github.com/web-vision/wv_deepltranslate-eap/pull/58
* [TASK] Update DI and configuration for glossary commands by @NarkNiro in https://github.com/web-vision/wv_deepltranslate-eap/pull/59
* [TASK] Remove unused table definition from `ext_tables.sql` by @NarkNiro in https://github.com/web-vision/wv_deepltranslate-eap/pull/60
* [BUGFIX] Ensure higher precedence of partial ordering by @NarkNiro in https://github.com/web-vision/wv_deepltranslate-eap/pull/61
* [TASK] Avoid duplicate PageTS file include with TYPO3 v12  by @NarkNiro in https://github.com/web-vision/wv_deepltranslate-eap/pull/62
* [TASK] Avoid undefined array key access in `DeeplService` by @NarkNiro in https://github.com/web-vision/wv_deepltranslate-eap/pull/63
* [TASK] Move flashMassages out auf services class by @NarkNiro in https://github.com/web-vision/wv_deepltranslate-eap/pull/64

### 4.1.1

* Update deepl icon generator by @NarkNiro in https://github.com/web-vision/wv_deepltranslate-eap/pull/52

### 4.1.0

#### What's Changed

* Extend client with simple logger by @NarkNiro in https://github.com/web-vision/wv_deepltranslate-eap/pull/41
* Repository alignment by @dot3media in https://github.com/web-vision/wv_deepltranslate-eap/pull/46
* Upgrade composer dependencies by @NarkNiro in https://github.com/web-vision/wv_deepltranslate-eap/pull/43
* Fix spelling from javascript file module name by @NarkNiro in https://github.com/web-vision/wv_deepltranslate-eap/pull/48
* Prepare Translate Hook to use in DeepL Plugins by @NarkNiro in https://github.com/web-vision/wv_deepltranslate-eap/pull/50

#### New Contributors

* @dot3media made their first contribution in https://github.com/web-vision/wv_deepltranslate-eap/pull/46

**Full Changelog**: https://github.com/web-vision/wv_deepltranslate-eap/compare/4.0.3...4.1.0

### 4.0.3

+ [BUGFIX] Fix loading issues with container services in https://github.com/web-vision/wv_deepltranslate-eap/pull/39

### 4.0.2

* [TASK] Prepare backend for enable_translated_content by @calien666 in https://github.com/web-vision/wv_deepltranslate-eap/pull/35
* [BUGFIX] remove whitspace from glossary entries by @calien666 in https://github.com/web-vision/wv_deepltranslate-eap/pull/36

**Full Changelog**: https://github.com/web-vision/wv_deepltranslate-eap/compare/4.0.1...4.0.2

### 4.0.1

* [BUGFIX] [Fix icon path](https://github.com/web-vision/wv_deepltranslate-eap/commit/04e9f0f03ece29c894ca8c78ca4981851a8894e0)
* [TASK] [Add ModifyButtonEvent](https://github.com/web-vision/wv_deepltranslate-eap/commit/4e9149d717e847b745a70fe78ce7db0bffe2923a)
* [BUGFIX] [Use proper Casing in files names](https://github.com/web-vision/wv_deepltranslate-eap/commit/c5a9e3df43229ab97a0779407e84cf6dedb6bf4a)
* [BUGFIX] [Allow glossary finding in TYPO3 v12](https://github.com/web-vision/wv_deepltranslate-eap/commit/9744d5738195eb22e4d9996fadc06e28ff969923)

**Full Changelog**: https://github.com/web-vision/wv_deepltranslate-eap/compare/4.0.0...4.0.1

### 4.0.0

- [TASK] Allow runners only on PR (032f3ed)
- [TASK] Prepare release step (5765d9a)
- [BUGFIX] Fix glossary-id handle in translate request (74cb9a8)
- [BUGFIX] Determine glossary correct on current page (31ca4ea)
- [BUGFIX] Determine correct Preview mode in TYPO3 v9 (bbbb3b0)
- [TASK] Add some todo markers and comments (bfa00d3)
- [BUGFIX] Avoid undefined array key warning in AllowLanguageSynchronizationHook  (e3947f5)
- [TASK] Display correct versions in core switch script (82eb8e6)
- [BUGFIX] Avoid undefined array key warning in LocalizationController (f5536d5)
- [TASK] Streamline doctrine/dbal usages (7a8bd31)
- [TASK] Remove obsolete version check from siteconfiguration tca (447c0bb)
- [BUGFIX] Guard LocalizationUtility::translate with (string) cast (9b4cba1)
- [TASK] Avoid instantiating PageRenderer in ext_localconf.php (4b8cd0e)
- [TASK] Replace deprecated TYPO3_MODE constant usage (f79ba80)
- [BUGFIX] Ensure docker compose v2 combat  (ddb1a6b)
- [TASK] Mark test classes final  (906d0b1)
- [TASK] Migrate documentation rendering to runTests.sh (72fc7d0)
- [DOCS] Ensure correct indentation in rst-files (e6d7aad)
- [BUGFIX] Avoid undefined array key warning in LocalizationController (f60cdcc)
- [DOCS] Update Settings.cfg for master to main switch (812be10)
- [BUGFIX] Avoid undefined array key warning in AllowLanguageSynchronizationHook (9c0d396)
- [TASK] Remove extbase persistence mapping (8edb0f1)
- [TASK] Move ajax extension config check in own class (4a6e12e)
- [TASK] Move Icon registry to Configuration/Icons.php (1529cc9)
- [TASK] Add TYPO3 v12 support to Build/Scripts/runTests.sh (3ffeee3)
- [TASK] Migrate to typo3/testing-framework (5d2dd68)
- [BUGFIX] Avoid doctrine/dbal fetchAssociative()  (5ec224f)
- [TASK] Update README badges (87bc51e)
- [TASK] Avoid rowCount() for select query (643ced1)
- [TASK] Refactor Services.yaml to Services.php  (a702578)
- [BREAKING] Remove Google Translate support (21fb2f5)
- [REMOVE] Settings Backend module and dependencies (cf05c15)
- [TASK] remove v9/v10 (688bf7f)

**Full Changelog**: https://github.com/web-vision/wv_deepltranslate-eap/compare/3.0.4...4.0.0

## 3.0.5

* [TASK] Update readme by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/287
* [BUGFIX] Trim glossary terms to prevent exception from DeepL API by @peterkraume in https://github.com/web-vision/wv_deepltranslate/pull/291
* [BUGFIX] Fix google translation error "Bad language pair" by @ErHaWeb in https://github.com/web-vision/wv_deepltranslate/pull/282
* [BUGFIX] Respect correct namespace for used `b13/container` class by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/277

## 3.0.4

* [BUGFIX] Access to undefined array keys in list view by @bigahuna in https://github.com/web-vision/wv_deepltranslate/pull/240
* [BUGFIX] Guard `LocalizationUtility::translate` with `(string)` cast by @sbuerk in https://github.com/web-vision/wv_deepltranslate/pull/255
* [BUGFIX] Avoid undefined array key warning in `LocalizationController` by @sbuerk in https://github.com/web-vision/wv_deepltranslate/pull/268
* [BUGFIX] Avoid undefined array key warning in `AllowLanguageSynchronizationHook` by @sbuerk in https://github.com/web-vision/wv_deepltranslate/pull/246
* [BUGFIX] Determine correct Preview mode in TYPO3 v9 by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/270
* [BUGFIX] Determine glossary correct on current page by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/271

**Full Changelog**: https://github.com/web-vision/wv_deepltranslate-eap/compare/3.0.3...3.0.4

## 3.0.3

* [TASK] Set branch alias for 3.0 by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/200
* [TASK] Avoid `rowCount()` for select query by @sbuerk in https://github.com/web-vision/wv_deepltranslate/pull/233
* [BUGFIX] Avoid doctrine/dbal `fetchAssociative()` by @sbuerk in https://github.com/web-vision/wv_deepltranslate/pull/236
* [BUGFIX] Fix Problem with translation no glossary given by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/235

**Full Changelog**: https://github.com/web-vision/wv_deepltranslate-eap/compare/3.0.2...3.0.3

## 3.0.2

[BUGFIX] Glossary is not used by @calien666 in #218
[TASK] Change Ext Icon by @calien666 in #220

## 3.0.1

* [BUGFIX] Fix runtime deprecation notice by @peterkraume in https://github.com/web-vision/wv_deepltranslate/pull/210
* [BUGFIX] Fix missing configuration object for localization wizard by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/211
* [BUGFIX] Update ButtonBarHook.php by @bigahuna in https://github.com/web-vision/wv_deepltranslate/pull/213
* [BUGFIX] Exclude fields in upgrade wizards by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/215
* [TASK] Add auto detect source language by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/214
* [BUGFIX] Glossary entries by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/212

## 3.0.0

* [BUGFIX] Prevent TypeError by adding string cast before explode() by @spoonerWeb in https://github.com/web-vision/wv_deepltranslate/pull/126
* [BUGFIX] v11: Allow pages always getting localized in RecordList by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/142
* [BUGFIX] Hide DeepL controls for not supported languages by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/143
* [BUGFIX] Backend module is always loaded, even if setting is false by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/157
* [BUGFIX] Fix active old backend module condition type handling by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/161
* [BUGFIX] Require PHP version in ext_emconf.php by @andreasfernandez in https://github.com/web-vision/wv_deepltranslate/pull/171
* [BUGFIX] language translate button and select by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/140
* [BUGFIX] DeepL Translation for fields with behaviour->allowLanguageSynchronization by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/141
* [BUGFIX] Use correct POST body for submitting the glossary by @koehnlein in https://github.com/web-vision/wv_deepltranslate/pull/172
* [BUGFIX] Fix choose language step skip by @philip-hartmann in https://github.com/web-vision/wv_deepltranslate/pull/178
* [BUGFIX] decode html special characters by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/187
* [BUGFIX] Fix ext_emconf version by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/186
* [BUGFIX] Trim source and target strings by @LimeUwe in https://github.com/web-vision/wv_deepltranslate/pull/175
* [BUGFIX] errors/warnings with PHP 8.1 by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/155
* [TASK] Move html utility function by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/122
* [TASK] Update extension dev dependencies by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/123
* [TASK] Change behaviour of translation button by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/134
* [TASK] Translation Check/Flag for Page Properties by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/137
* [TASK] Code refactoring of Hooks and Language behaviour by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/138
* [TASK] add news extension as dev-require by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/136
* [DOC] Restructuring documentation by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/127
* [DOC] Documentation by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/149
* [TASK] Introduce GitHub actions powered workflow by @sbuerk in https://github.com/web-vision/wv_deepltranslate/pull/147
* [TASK] Improve variable types by @koehnlein in https://github.com/web-vision/wv_deepltranslate/pull/169
* [TASK] Remove stripTag function in translateHook by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/163
* [TASK] Add conflicts to recordlist_thumbnail by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/156
* [TASK] Introduce issue templates by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/145
* [TASK] Use `core-testing-*` images from `ghcr.io` by @sbuerk in https://github.com/web-vision/wv_deepltranslate/pull/181
* [TASK] Add sponsor to Documentation by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/183
* [TASK] Make glossary sync more tolerant of outdated IDs by @koehnlein in https://github.com/web-vision/wv_deepltranslate/pull/173
* [TASK] Create docs translate prefix by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/184
* [TASK] Refactoring glossary handling by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/165
* [TASK] task template by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/190
* [TASK] Introduce Upgrade instructions by @calien666 in https://github.com/web-vision/wv_deepltranslate/pull/194
* [FEATURE] Rework and centralize api client operation by @NarkNiro in https://github.com/web-vision/wv_deepltranslate/pull/189

## 2.3.1

* [BUGFIX] Fix exception due to missing use statement by @sypets in #108

## 2.3.0

* [BUGFIX] Add php doc blocks by @spoonerWeb in #94
* [BUGFIX] Fix glossary sync persistance by @sypets in #100
* [BUGFIX] Fix undefined array key in DataHandlerHook by @sypets in #101
* [TASK] Run CGL fix by @sypets in #103
* [FEATURE] add supported languages automatically from API by @calien666 in #107

## v2.2.2

* [TASK] Update supported deepl api languages by @ayacoo in #64
* [BUGFIX] localization wizard with EXT:container by @achimfritz in #56
* [BUGFIX] do not try to log expected exception by @achimfritz in #80
* [TASK] #84 fix translate request return type
* [TASK] Set google-translate service deprecated
* [TASK] #89 Update pagerender hook method for css resources and set ext prefix
* [BUGFIX] #90 move inline settings js to requireJS

## v2.2.1

- Fix third party translations

## v2.2.0

- Glossary Feature (https://support.deepl.com/hc/en-us/sections/360005269340-Glossary)

## v2.0.1

- TYPO3 v11.5.5 compatibility added
- Fixed broken translation from list module

## v2.0.0

- TYPO3 v11 compatibility added
- An issue regarding translation of inline elements in container fixed (https://github.com/b13/container/issues/188)
- Added formality option into deepl settings (https://github.com/web-vision/wv_deepltranslate/issues/21)
