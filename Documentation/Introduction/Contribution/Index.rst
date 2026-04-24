..  _contribution:

Contribution
============

Contributions are essential to the success of open source projects, but they are
by no means limited to contributing code. Much more can be done, for example by
improving the `documentation <https://docs.typo3.org/p/web-vision/deepltranslate-core/main/en-us/>`__
or answering questions on `stackoverflow.com <https://stackoverflow.com/questions/tagged/deepltranslate-core>`_.

Contribution workflow
---------------------

#.  Please always create an issue on `Github <https://github.com/web-vision/deepltranslate-core/issues>`__
    before starting a change. This is very helpful to understand what kind of problem the
    pull request solves, and whether your change will be accepted.

#.  Bug fixes: Please describe the nature of the bug you wish to report and provide
    how to reproduce the problem. We will only accept bug fixes if we can
    can reproduce the problem.

#.  Features: Not every feature is relevant to the majority of users.
    In addition: We do not want to complicate the usability of this extension for a marginal feature.
    It helps to have a discussion about a new feature before
    before opening a pull request.

#.  Please always create a pull request based on the updated release branch. This
    ensures that the necessary quality checks and tests are performed as a quality
    can be performed.

Contribution information
------------------------

Commit message
~~~~~~~~~~~~~~

*   This repository uses similar subject prefixes like convential commits, but
    follows the known prefixes used in the TYPO3 Community:

    *   `[FEATURE] ` instead of `feat: ` for changes introducing new features.
        Requires a `Feature-*.rst` changelog file.
    *   `[BUGFIX] ` instead of `fix: `
    *   `[TASK] ` instead of `chore: ` for any task.
    *   `[DOCS] ` instead of `docs: ` for documentation or markdown file related
        changes.
    *   `[!!!]` before the other prefixes instead of `!` or `BREAKING` to hint
        breaking changes. Requires a `Breaking-*.rst` changelog file.

    with special `[!!!]` before task or feature indicating breaking changes.

Documentation and Changelog
~~~~~~~~~~~~~~~~~~~~~~~~~~~

*   Breaking changes indicated with prefix `[!!!]` requires a `Breaking-*.rst`
    changelog file.
*   Features indicated with prefix `[FEATURE]` requires a `Feature-*.rst`
    changelog file.
*   Tasks deprecation methods, classes or functionality needs to be documented
    adding `Deprecation-*.rst` changelog files.
*   In case important information needs to be documented `Important-*.rst`
    changelog files could be provided.

Documentation is rendered locally using:

..  code-block:: bash

    Build/Scripts/runTests.sh -s renderDocumentation

and can ge viewed in the browser opening `Documentation-GENERATED-temp/Index.html`
for example on linux with:

..  code-block:: bash

    xdg-open Documentation-GENERATED-temp/Index.html

Executing toolchain
~~~~~~~~~~~~~~~~~~~

All related development tools are executed using the `Build/Scripts/runTests.sh`
command dispatcher.

Available general options:

*   `-b <docker|podman>` allows to set the container system to use in case both
    are available, otherwise it is determined. `podman` is used over `docker`
    if not enforced using this option.

*   `-s <suite>` selectes the suite (command) to execute, see `-h` for full
    description of available options and suites.

*   `-p <8.2|8.2|8.3|8.4|8.5>` defines the PHP version to use for PHP related
    suites and commands, for example phpunit, phpstan or composer operations.

*   `-x` enforces xdebug profile=debug for php script executions, use-ful to
    debug unit- or functional tests.

*   `-d <sqlite|mysql|mariadb|postgres>` selects the database to use for
    functional tests and starts/stops/cleans the selected container.

*   `-i <version>` allows to select the database server version for MariaDB,
    MySQL or PostgreSQL. See `-h` for the list of available options. Used for
    functional tests.

Examples
~~~~~~~~

..  code-block:: bash
    :caption: functional tests for TYPO3 v13.4 with PHP 8.2

    Build/Scripts/runTests.sh -t 13 -p 8.2 -s composerUpdate && \
    Build/Scripts/runTests.sh -t 13 -p 8.2 -s functional -d sqlite && \
    Build/Scripts/runTests.sh -t 13 -p 8.2 -s functional -d mariadb -i 10.7

..  code-block:: bash
    :caption: functional test for TYPO3 v14.3 with PHP 8.5

    Build/Scripts/runTests.sh -t 14 -p 8.5 -s composerUpdate && \
    Build/Scripts/runTests.sh -t 14 -p 8.5 -s functional -d sqlite && \
    Build/Scripts/runTests.sh -t 14 -p 8.5 -s functional -d mariadb -i 10.7

..  code-block:: bash
    :caption: unit tests for TYPO3 v13.4 with PHP 8.5

    Build/Scripts/runTests.sh -t 13 -p 8.5 -s composerUpdate && \
    Build/Scripts/runTests.sh -t 13 -p 8.5 -s unit && \
    Build/Scripts/runTests.sh -t 13 -p 8.5 -s unitRandom

..  code-block:: bash
    :caption: unit tests for TYPO3 v14.3 with PHP 8.2

    Build/Scripts/runTests.sh -t 14 -p 8.2 -s composerUpdate && \
    Build/Scripts/runTests.sh -t 14 -p 8.2 -s unit && \
    Build/Scripts/runTests.sh -t 14 -p 8.2 -s unitRandom

..  code-block:: bash
    :caption: Code style fixer

    Build/Scripts/runTests.sh -t 13 -p 8.2 -s composerUpdate && \
    Build/Scripts/runTests.sh -t 13 -p 8.2 -s cgl

..  code-block:: bash
    :caption: Static code analyzer - analyze

    Build/Scripts/runTests.sh -t 13 -p 8.2 -s composerUpdate && \
    Build/Scripts/runTests.sh -t 13 -p 8.2 -s phpstan && \
    Build/Scripts/runTests.sh -t 14 -p 8.2 -s composerUpdate && \
    Build/Scripts/runTests.sh -t 14 -p 8.2 -s phpstan

..  code-block:: bash
    :caption: Static code analyzer - regenerate PHPStan baseline

    Build/Scripts/runTests.sh -t 13 -p 8.2 -s composerUpdate && \
    Build/Scripts/runTests.sh -t 13 -p 8.2 -s phpstanGenerateBaseline && \
    Build/Scripts/runTests.sh -t 14 -p 8.2 -s composerUpdate && \
    Build/Scripts/runTests.sh -t 14 -p 8.2 -s phpstanGenerateBaseline
