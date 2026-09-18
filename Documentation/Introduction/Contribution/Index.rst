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
key in the environment variable `DEEPL_AUTH_KEY`:

..  code-block:: bash
    :caption: functional tests against the real DeepL API

    DEEPL_AUTH_KEY=<your-api-key> Build/Scripts/runTests.sh -t 13 -p 8.2 -s functionalDeepLApi
