# Contributing to deepltranslate-core

Thank you for helping. This file explains how a change gets into this
extension: which branch, how to test it, how to write the commit and what a
pull request needs to be merged.

## Table of contents

- [Issues and security](#issues-and-security)
- [Branches](#branches)
- [Getting started](#getting-started)
- [Tests and checks](#tests-and-checks)
- [Code rules](#code-rules)
- [Documentation and changelog](#documentation-and-changelog)
- [Commit messages](#commit-messages)
- [Pull requests](#pull-requests)
- [Other DeepL extensions](#other-deepl-extensions)

## Issues and security

- Bugs and feature requests are GitHub issues, with the templates offered
  when you open one. Include the TYPO3 version, the version of this extension
  and the steps to reproduce.
- **Security issues are never reported publicly.** See
  [SECURITY.md](SECURITY.md).
- The maintainers track their work in an internal tracker, project `DPL`.
  That is why commits and pull requests refer to `DPL-123`. You do not need
  access to it.

## Branches

| Branch | Version | TYPO3        | PHP        | State                         |
|--------|---------|--------------|------------|-------------------------------|
| `main` | 6.x     | 13.4, 14.3   | 8.2 to 8.5 | development of the next 6.x   |
| `5`    | 5.x     | 12.4, 13.4   | 8.1 to 8.4 | bug fixes and security fixes  |
| `4`, `3.0` | 4.x, 3.x | -       | -          | end of life, read-only        |

- Open a pull request against `main`. A fix that is needed in 5.x as well is
  a second pull request against `5`, made after the first one, from a branch
  with the suffix `-5` (`bugfix/my-fix` and `bugfix/my-fix-5`). Its title
  ends with `(5.x)`. The maintainers can do that second one for you.
- The branch `5` has its own `CONTRIBUTING.md`. Read that one for a change
  there: the supported versions and some code rules differ.

## Getting started

You need git, bash and docker or podman. Everything else runs in containers
through `Build/Scripts/runTests.sh`, the same script the CI uses.

```bash
git clone git@github.com:web-vision/deepltranslate-core.git
cd deepltranslate-core
Build/Scripts/runTests.sh -h                        # all options and suites
Build/Scripts/runTests.sh -t 13 -s composerUpdate   # install for TYPO3 v13
Build/Scripts/runTests.sh -t 13 -s unit
```

- `-t` selects the TYPO3 version (`13`, default, or `14`). The installation
  in `.Build/` exists once: run `-s composerUpdate` with the same `-t` before
  the suites of that version, and never two versions at the same time.
- `-b docker` or `-b podman` selects the container binary. Without it,
  podman is used when it is installed.
- `-p` selects the PHP version (default 8.2).

## Tests and checks

A pull request is merged when these are green for TYPO3 v13 and v14. Run them
locally before you push:

| Check                         | Command                                              |
|-------------------------------|------------------------------------------------------|
| Coding style (check only)     | `Build/Scripts/runTests.sh -t 13 -s cgl -n`          |
| Coding style (fix)            | `Build/Scripts/runTests.sh -t 13 -s cgl`             |
| PHPStan                       | `Build/Scripts/runTests.sh -t 13 -s phpstan`         |
| PHP lint                      | `Build/Scripts/runTests.sh -t 13 -s lintPhp`         |
| TypoScript lint               | `Build/Scripts/runTests.sh -t 13 -s lintTypoScript`  |
| Unit tests                    | `Build/Scripts/runTests.sh -t 13 -s unit`            |
| Functional tests              | `Build/Scripts/runTests.sh -t 13 -s functional`      |
| Functional tests, other DBMS  | `... -s functional -d mariadb` (also `mysql`, `postgres`) |
| Exception codes unique        | `Build/Scripts/runTests.sh -s checkExceptionCodes`   |
| Test method names             | `Build/Scripts/runTests.sh -s checkTestMethodsPrefix`|
| UTF-8 without BOM             | `Build/Scripts/runTests.sh -s checkBom`              |
| Documentation renders         | `Build/Scripts/runTests.sh -s renderDocumentation`   |

The same with `-t 14` after `-s composerUpdate -t 14`.

- The functional tests talk to a DeepL mock server container, not to DeepL.
  They need no API key and cost nothing.
- `-s functionalDeepLApi` runs the tests of the group `deepl-real-api`
  against the real DeepL API. It needs your own key in `DEEPL_AUTH_KEY`, is
  billed per character and never runs in CI. Read the key with
  `read -rs DEEPL_AUTH_KEY && export DEEPL_AUTH_KEY` so it stays out of your
  shell history.
- A bug fix comes with a test that fails without it. A new feature comes
  with tests.

## Code rules

- `declare(strict_types=1);` in every PHP file, classes `final` unless they
  are meant to be extended, dependencies as `readonly` promoted constructor
  properties.
- Services are stateless. They carry no data from one call to the next.
- Dependency injection through Symfony attributes (`#[AsEventListener]`,
  `#[AsAlias]`, `#[Autoconfigure]`, `#[AutoconfigureTag]`, ...). `Services.yaml`
  keeps the defaults and the resource. `Services.php` only loads the
  directories per TYPO3 version and registers what needs code.
- **No TYPO3 version checks inside classes.** Code that differs between v13
  and v14 lives in `Core13/` and `Core14/` (namespaces `...\Core\Core13\` and
  `...\Core\Core14\`), behind an interface in `Classes/`. `Services.php` loads
  only the directory of the running version.
- Classes marked `@internal` are no public API, even when other DeepL
  extensions use them. A change to them is checked against those extensions
  (see [Other DeepL extensions](#other-deepl-extensions)).
- Every exception gets a unique code, the Unix timestamp of the moment you
  write it.
- Test methods use the `#[Test]` attribute and do not start with `test`.
- Coding style is PER-CS 1.0 (`Build/php-cs-fixer/php-cs-rules.php`), PHPStan
  runs on level 8 with a baseline per TYPO3 version in `Build/phpstan/`. A
  change does not add to the baselines.

## Documentation and changelog

- The documentation is reStructuredText in `Documentation/`, rendered with
  `-s renderDocumentation`. The rendered result is not committed.
- A feature, a breaking change, a deprecation or a fix an editor or
  integrator notices gets a changelog entry in
  `Documentation/Changelog/<major.minor>/`, named like the TYPO3 Core
  changelog: `Feature-<Topic>.rst`, `Breaking-...`, `Deprecation-...`,
  `Important-...`. It is part of the same commit.

## Commit messages

The [TYPO3 Core commit message rules](https://docs.typo3.org/m/typo3/guide-contributionworkflow/main/en-us/Appendix/CommitMessage.html):

```
[BUGFIX] DPL-90: Translate link titles and alt texts

Explain why the change is needed and what it does, not how the diff
looks. Wrap the body at 72 characters.

Resolves: #427
```

- Subject: a tag (`[FEATURE]`, `[BUGFIX]`, `[TASK]`, `[DOCS]`, `[SECURITY]`,
  `[!!!]` in front for a breaking change), the internal issue if there is one,
  imperative mood, at most 52 characters where possible.
- A GitHub issue goes into the footer (`Resolves: #427`) or at the end of
  the subject (`(#427)`).

## Pull requests

- **One pull request carries one commit.** The title of the pull request is
  the subject of the commit. Review changes are amended into the commit and
  force pushed, not added as new commits.
- Rebase onto the current `main` instead of merging it in. The branch is
  merged with "rebase and merge", the only method allowed.
- Required: one approving review and the checks `code quality with core v13
  (8.2)`, `all tests with core v13 (8.2)`, `all tests with core v13 (8.5)`,
  the same for v14, and `render documentation`.
- Name the branch `<type>/<topic>`, for example `bugfix/670-inline-markup` or
  `feature/558-inline-localize`.

## Other DeepL extensions

deepltranslate-core is the base of `deepltranslate-glossary`, `-auto-renew`,
`-mass` and `-assets`, which use its events, its DataHandler command
`deepltranslate`, its site configuration fields and some of its `@internal`
classes. They require this extension with a range on its minor version
(`~6.1.0`). A change to anything they use, and every new minor version, needs
a matching change there. The maintainers coordinate that: say in your pull
request when you know a change affects them.

The extension itself builds on `web-vision/deepl-base` and
`web-vision/deeplcom-deepl-php` (the DeepL PHP SDK).
