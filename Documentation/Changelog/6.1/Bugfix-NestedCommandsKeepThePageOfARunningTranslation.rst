..  _bugfix-nested-commands-keep-the-page-of-a-running-translation-1792060800:

====================================================================
Bugfix: Nested DeepL commands keep the page of a running translation
====================================================================


Description
===========

The DataHandler command `deepltranslate` remembers the record it
translates for the whole request. The site, the page, the glossary and the
translation context of every translated field are taken from that record.
A `deepltranslate` command issued while another record is still being
localized replaced it for the rest of that localization.
`web-vision/deepltranslate-auto-renew` issues such commands from its
DataHandler hooks.

The remaining fields of the running localization were then translated
with the site, the glossary and the translation context of the other
record, and its page was marked as translated with DeepL. With the other
record on a site without the target language, the rest of the
localization failed.

Impact
======

A `deepltranslate` command issued within another one restores the record
of the running command when it is done. A command issued on its own keeps
its record for the rest of the request, as before.

See pull request `#696
<https://github.com/web-vision/deepltranslate-core/pull/696>`__.
