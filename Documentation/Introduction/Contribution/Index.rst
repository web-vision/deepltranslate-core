..  include:: /Includes.rst.txt

..  _contribution:

Contribution
============

Contributions are essential to the success of open source projects, but they are by no means
limited to contributing code. Much more can be done, for example by
improving the `documentation <https://docs.typo3.org/p/web-vision/deepltranslate-core/main/en-us/>`__.

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

Tests against the real DeepL API
--------------------------------

Functional tests in the PHPUnit group `deepl-real-api` send content to the real
DeepL API instead of the mock server. Every run is billed per character, so
the `functional` suite and CI exclude them. Run them locally with your own API
key in the environment variable `DEEPL_AUTH_KEY`. Read the key with `read -rs`,
it is neither shown nor written to the shell history, which a key typed into
the command line would be:

..  code-block:: bash
    :caption: functional tests against the real DeepL API

    read -rs DEEPL_AUTH_KEY && export DEEPL_AUTH_KEY
    Build/Scripts/runTests.sh -t 13 -p 8.2 -s functionalDeepLApi
    unset DEEPL_AUTH_KEY

A failing test shows the translation and the raw answer of DeepL. To keep the
request text, the answer and the billed characters of every call, name a file
below the extension directory in `DEEPL_REAL_API_LOG`. The API key is never
written to it. The key is read the same way:

..  code-block:: bash
    :caption: functional tests against the real DeepL API, with a log of every call

    read -rs DEEPL_AUTH_KEY && export DEEPL_AUTH_KEY
    CI_PARAMS="-e DEEPL_REAL_API_LOG=$PWD/.Build/deepl-real-api.jsonl" \
        Build/Scripts/runTests.sh -t 13 -p 8.2 -s functionalDeepLApi
    unset DEEPL_AUTH_KEY
