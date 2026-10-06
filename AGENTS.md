# Agent instructions

Instructions for coding agents working in this repository. `CLAUDE.md`
imports this file. Read [CONTRIBUTING.md](CONTRIBUTING.md) first: its rules
apply to you in full. This file adds what an agent needs on top of it.

## Know which line you are on

This file belongs to the branch `5`: deepltranslate-core 5.x, TYPO3 12.4 and
13.4, PHP 8.1 to 8.4, bug fixes and security fixes only. **The branch you
work on decides the rules.** For work on `main` (6.x), switch to it and follow
its own `AGENTS.md`.

Check before you start:

```bash
git branch --show-current     # must print 5
git status -sb
```

## Backports

Most work on this branch is a backport of a fix made on `main`. The code of
`main` does not fit here as it is:

- `main` puts version differences into `Core13/` and `Core14/`. This branch
  has neither, and does not get them: a difference between v12 and v13 is a
  `Typo3Version` check at the place or a class in `Classes/Override/Core12/`,
  as the branch already does.
- `main` registers event listeners with `#[AsEventListener]`. Here they are
  tagged in `Configuration/Services.yaml`, TYPO3 v12 has no such attribute.
- `main` requires PHP 8.2 and deepl-base 2.x. Here the floor is PHP 8.1 and
  deepl-base 1.x: no `readonly` classes, no DNF types, no typed class
  constants, no `#[\Override]`, no deepl-base 2.x or TYPO3 v14 API.
- `main` uses PER-CS, this branch PSR-2. Run this branch's `cgl`.
- `main` adds a changelog entry in `Documentation/Changelog/`. This branch
  has none, do not create it.

Port the behaviour, keep the commit subject and body of `main` and adapt the
body where the change differs. Cherry-pick when it applies cleanly, then
check every hunk against the list above. Say in the result what had to be
adapted.

## Rules

- **Never write to a remote** (push, pull request, issue, comment, review,
  merge) unless the maintainer asks for exactly that.
- **Never credit a tool or a model** in commits, pull requests, issues, code
  comments or documentation. The human who submits the change is its author.
- Scratch files, plans, reports and downloads go into `.agent/` (git
  ignored), never into the tracked tree and never into `/tmp`.
- Verify by running, not by recalling. Say what you ran and what you did not
  run.
- An issue reference (`DPL-123`, `#427`) is written only when it is known to
  exist.

## Running the test harness as an agent

- **Set `CI=true`.** Without a terminal, `runTests.sh` fails with "the input
  device is not a TTY" unless `CI` is `true`:

  ```bash
  export CI=true
  Build/Scripts/runTests.sh -b docker -t 12 -p 8.1 -s composerUpdate
  Build/Scripts/runTests.sh -b docker -t 12 -p 8.1 -s cgl -n
  Build/Scripts/runTests.sh -b docker -t 12 -p 8.1 -s phpstan
  Build/Scripts/runTests.sh -b docker -t 12 -p 8.1 -s unit
  Build/Scripts/runTests.sh -b docker -t 12 -p 8.1 -s functional
  ```

  Then the same with `-t 13 -p 8.2`, starting with `composerUpdate`.
- **One TYPO3 version at a time.** `.Build/` holds the installation of the
  last `composerUpdate`. Never run two `runTests.sh` calls of this checkout in
  parallel.
- `composerUpdate` rewrites `composer.json` while it runs and restores it
  afterwards. Do not interrupt it, never commit a `composer.json` changed by
  it.
- `-s cgl` changes files, `-s cgl -n` only checks.
- **Never run `-s functionalDeepLApi`** unless the maintainer asks. It is
  billed. Never print, log or write a DeepL key anywhere.
- Done means: the suites of the changed area green on **both** v12 (PHP 8.1)
  and v13, `cgl -n` and `phpstan` green on both.

## Commits and pull requests

- One commit per pull request, title ending in `(5.x)`, branch name ending in
  `-5`. Amend and force push (`--force-with-lease`) when asked to update it.
- Rebase onto `origin/5`, never merge it in.
