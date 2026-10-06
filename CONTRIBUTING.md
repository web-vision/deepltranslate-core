# Contributing to deepltranslate-core 5.x

This is the branch `5`: deepltranslate-core 5.x for TYPO3 12.4 and 13.4. It
receives bug fixes and security fixes. New features are developed on `main`
(6.x), whose `CONTRIBUTING.md` describes the extension as it is developed
today. This file describes the rules of this branch.

## Table of contents

- [Issues and security](#issues-and-security)
- [Branches](#branches)
- [Getting started](#getting-started)
- [Tests and checks](#tests-and-checks)
- [Code rules on this branch](#code-rules-on-this-branch)
- [Documentation](#documentation)
- [Commit messages](#commit-messages)
- [Pull requests](#pull-requests)

## Issues and security

- Bugs are GitHub issues, with the templates offered when you open one. Say
  that you use 5.x, and which TYPO3 version.
- **Security issues are never reported publicly.** See
  [SECURITY.md](SECURITY.md). 5.x receives security fixes until the date
  named there.
- The maintainers track their work in an internal tracker, project `DPL`.
  That is why commits refer to `DPL-123`. You do not need access to it.

## Branches

| Branch | Version | TYPO3        | PHP        | State                         |
|--------|---------|--------------|------------|-------------------------------|
| `main` | 6.x     | 13.4, 14.3   | 8.2 to 8.5 | development                   |
| `5`    | 5.x     | 12.4, 13.4   | 8.1 to 8.4 | bug fixes and security fixes  |
| `4`, `3.0` | 4.x, 3.x | -       | -          | end of life, read-only        |

- A fix is made on `main` first, when `main` has the bug too, and then
  brought to this branch in a second pull request. Name its branch after the
  one of `main` with the suffix `-5` (`bugfix/my-fix-5`) and end its title
  with `(5.x)`.
- A fix that only concerns 5.x is a pull request against `5` directly.
- **A backport is written for this branch.** The code of `main` uses APIs,
  PHP features and a structure that 5.x does not have. Adapt the change to
  the rules below, do not copy it.

## Getting started

You need git, bash and docker or podman. Everything else runs in containers
through `Build/Scripts/runTests.sh`, the same script the CI uses.

```bash
git clone git@github.com:web-vision/deepltranslate-core.git
cd deepltranslate-core
git switch 5
Build/Scripts/runTests.sh -h                        # all options and suites
Build/Scripts/runTests.sh -t 12 -s composerUpdate   # install for TYPO3 v12
Build/Scripts/runTests.sh -t 12 -s unit
```

- `-t` selects the TYPO3 version (`12`, default, or `13`). The installation
  in `.Build/` exists once: run `-s composerUpdate` with the same `-t` before
  the suites of that version, and never two versions at the same time.
- `-p` selects the PHP version (default 8.2). TYPO3 v12 is tested on PHP 8.1
  in CI: run v12 with `-p 8.1` to catch syntax 8.1 does not know.
- `-b docker` or `-b podman` selects the container binary. Without it,
  podman is used when it is installed.

## Tests and checks

A pull request is merged when these are green for TYPO3 v12 and v13:

| Check                         | Command                                                  |
|-------------------------------|----------------------------------------------------------|
| Coding style (check only)     | `Build/Scripts/runTests.sh -t 12 -p 8.1 -s cgl -n`       |
| PHPStan                       | `Build/Scripts/runTests.sh -t 12 -p 8.1 -s phpstan`      |
| PHP lint                      | `Build/Scripts/runTests.sh -t 12 -p 8.1 -s lintPhp`      |
| Unit tests                    | `Build/Scripts/runTests.sh -t 12 -p 8.1 -s unit`         |
| Functional tests              | `Build/Scripts/runTests.sh -t 12 -p 8.1 -s functional`   |
| Functional tests, other DBMS  | `... -s functional -d mariadb` (also `mysql`, `postgres`) |
| Exception codes unique        | `Build/Scripts/runTests.sh -s checkExceptionCodes`       |

The same with `-t 13 -p 8.2` after `-s composerUpdate -t 13 -p 8.2`.

- The functional tests talk to a DeepL mock server container, not to DeepL.
- `-s functionalDeepLApi` runs tests against the real DeepL API. It needs
  your own key in `DEEPL_AUTH_KEY`, is billed and never runs in CI.
- A bug fix comes with a test that fails without it.

## Code rules on this branch

The rules of `main` apply where TYPO3 v12 and PHP 8.1 allow them. These are
the differences:

- **PHP 8.1.** No `readonly` classes, no disjunctive normal form types, no
  typed class constants, no `#[\Override]`. `readonly` promoted properties
  are fine.
- **Event listeners are registered in `Configuration/Services.yaml`** with
  the tag `event.listener`. TYPO3 v12 does not know `#[AsEventListener]`.
- **No `Core12/` or `Core13/` directories.** Where v12 and v13 differ, this
  branch checks the version at that place (`Typo3Version`) or overrides a
  class for v12 in `Classes/Override/Core12/`, selected in
  `ext_localconf.php`. Follow that pattern, keep such places few, and do not
  restructure the branch in a fix.
- No API of TYPO3 v14 and nothing that needs deepl-base 2.x: this branch
  uses deepl-base 1.x.
- Coding style is PSR-2 with the PHP 7.4 migration rules
  (`Build/php-cs-fixer/php-cs-rules.php`), not the PER-CS of `main`. PHPStan
  runs on level 8 with a baseline per TYPO3 version in `Build/phpstan/Core12`
  and `Core13`. A change does not add to the baselines.
- Every exception gets a unique code, the Unix timestamp of the moment you
  write it. New tests use the `#[Test]` attribute.

## Documentation

The documentation in `Documentation/` describes 5.x. Change it when a fix
changes what it describes. This branch has no `Documentation/Changelog/`,
and `CHANGELOG.md` is no longer maintained: a fix needs no changelog entry.

## Commit messages

The [TYPO3 Core commit message rules](https://docs.typo3.org/m/typo3/guide-contributionworkflow/main/en-us/Appendix/CommitMessage.html),
the same as on `main`:

```
[BUGFIX] DPL-90: Translate link titles and alt texts

Explain why the change is needed and what it does. Wrap the body at 72
characters.
```

A backport keeps the subject and the body of the commit on `main` and adapts
the body where the 5.x change differs.

## Pull requests

- One pull request carries one commit, its title the subject of the commit,
  with `(5.x)` at the end. Review changes are amended and force pushed.
- Rebase onto the current `5`. The branch is merged with "rebase and merge".
- Required: one approving review and the checks `code quality with core v12
  (8.1)`, `all tests with core v12 (8.1)`, `all tests with core v12 (8.4)`,
  `code quality with core v13 (8.2)`, `all tests with core v13 (8.2)`,
  `all tests with core v13 (8.5)`.
