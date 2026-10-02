..  include:: /Includes.rst.txt

Known issues
============

Translation options not shown
-----------------------------

When API key is not set, *deepltranslate_core* disables all functions.
Go to :ref:`Settings <extensionConfiguration>` and fix it. Clear cache
after this.

Inline markup in translated rich text
-------------------------------------

Rich text is sent to DeepL as XML. Before it is sent, it is prepared for the
way DeepL places inline markup, and the preparation is reverted in the
translation: inline elements like `<a>`, `<em>` or `<strong>` are sent as
`non_splitting_tags`, so a sentence is translated as a whole across its markup,
links touching each other stay separate links, `<sup>` and `<sub>` stay on the
word they belong to, and characters XML does not allow are removed. Some
results differ from the source on purpose or because of DeepL:

*   Words glued to an element by mistake, like `Our<strong>new</strong>offer`,
    come back with spaces, `Unser <strong>neues</strong> Angebot`. The
    translation is new text, and readable spacing is the better result.

*   A single styled letter of a word, like `i<em>Phone</em>` or
    `e<strong>Mail</strong>`, has no word of its own. DeepL can move the letter
    into or out of the element, for example `<em>iPhone</em>`, or drop the
    element. No text is lost.

*   A link on part of a word can be lost when the translation is one word,
    `Fahr<a>rad</a>` becomes `bicycle` in English. The translation is kept, a
    warning is logged, and the editor gets a flash message naming the field
    and the link, so it can be added again. The message is kept in the session
    of the editor, so it outlives the AJAX request of the localization wizard
    and is shown with the next backend page. Callers of
    :php:`\WebVision\Deepltranslate\Core\Service\DeeplService::translateContent()`
    find the lost links in
    :php:`\WebVision\Deepltranslate\Core\Domain\Dto\TranslateContext::getLostLinks()`.

The preparation adds a short reference number to every inline element of the
request. Tags are not billed, but they count for the request size limit of
DeepL (128 KiB). For the rich text of the development instances the request
grows by about 12 %, for a paragraph dense with inline markup by about 45 %.

Content format of translated fields
-----------------------------------

Rich text is sent to DeepL as XML and converted back, plain text is escaped
before and decoded after the translation. Which way a field takes is its
content format, :php:`\WebVision\Deepltranslate\Core\Domain\Enum\ContentFormat`,
resolved from the TCA of the field by
:php:`\WebVision\Deepltranslate\Core\Service\ContentFormatResolver`.

Fields of inline children
~~~~~~~~~~~~~~~~~~~~~~~~~

When a record is translated, TYPO3 localizes its inline children along with it,
for example the file references of a content element, and calls the DeepL hook
for their fields as well. These fields are resolved in the table and the record
type of the child, not in the record the translation was started for. A file
reference title like `Ref <title> & co` is translated as plain text, and a child
field named like a rich text field of the parent is not handled as rich text.

The `overrideChildTca` of the inline parent field applies as in the backend
form, before the `columnsOverrides` of the record type, if the record is the
inline child of exactly one parent record. A field it turns into rich text is
translated as rich text, a rich text field it turns into a plain text area or a
code editor is not.

In a workspace, the fields are resolved with the workspace version of the
record, whose values TYPO3 passes to the hook. A content element whose CType
was changed in the workspace, for example from `bullets` to `text`, is
translated in the format of its new type.

Code editor fields
~~~~~~~~~~~~~~~~~~

HTML edited in a code editor (`renderType` `codeEditor` with the format
`html`, the default), for example the content element "Plain HTML" (CType
`html`), is translated like rich text. The content of `<script>` and `<style>`
is sent with `ignore_tags` and kept byte by byte, `<`, `&&` and `-->` included.
Without it, the XML tag handling lets DeepL translate strings in them, for
example `var label = "Opening hours"` becomes `var label = "Öffnungszeiten"`.
Attribute values such as `title`, `aria-label` or `data-*` are not translated,
as before. The content is parsed as HTML, so it is written back in normalized
form, for example `<br />`, quoted attributes and characters instead of named
entities, and comments with `--` inside are made valid XML, see
"Inline markup in translated rich text" above.

On TYPO3 12 the code editor is provided by `typo3/cms-t3editor`. Without it,
the field of "Plain HTML" is a plain textarea, and this extension declares its
format `html` in the TCA, so it is translated the same way. The textarea the
editor sees does not change.

Code in other formats, for example TypoScript, is not sent to DeepL. The
translation keeps the code of the source record unchanged.

Attributes
~~~~~~~~~~

The attribute names and values of every element come back as written in the
source. The conversion to XML dropped attribute names XML does not allow,
like `@click` of Alpine.js, `#ref` of Vue or `[disabled]` and `(click)` of
Angular, it lowercased names, kept only the first of duplicate attributes and
escaped values like `onclick="if (a < b && c) …"`. An element whose attributes
would change is sent with a number in the attribute `dlt-a`, and its attribute
text is taken from the source again. Other elements are sent as before.
Attribute values are not translated, as before, DeepL does not translate them.
Not kept are the space before `>`, the `/` of a self-closing tag and a missing
end tag of `<script>`, which is added.

Callers without a content format
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Add-ons which call :php:`\WebVision\Deepltranslate\Core\Service\DeeplService::translateContent()`
without setting a content format get the content format `Unknown`. Their
content is handled as HTML and the entities of the result are decoded, as
before, a non-breaking space included. Content the HTML parser would change is
handled as plain text, for example `a<b`, `I </3 you`, `Ref <title> & co` or
`Use <b> for bold`, where the element is never closed. Rich text written by the
rich text editor closes every element and is handled as HTML. An `&` that
starts no entity, like in `<p>Q&A</p>`, is kept by the HTML parser and does not
make the content plain text.

Add-ons should set the content format with
:php:`\WebVision\Deepltranslate\Core\Domain\Dto\TranslateContext::setContentFormat()`.

Stored rich text
~~~~~~~~~~~~~~~~

The translated rich text is written the way TYPO3 stores it: void elements as
`<br />`, `<hr />` and `<img … />`, and `<` and `>` in attribute values escaped
as `&lt;` and `&gt;`, unless the source writes the attributes of the element
differently, see above. A raw `>` in a `title` cuts the link apart in the
frontend, and an `<hr>` on its own line is removed when the translation is saved
through the rich text transformation. Line breaks are stored as LF instead of
CRLF.

Failed translations
~~~~~~~~~~~~~~~~~~~

A field which cannot be translated keeps the text of the source language, as
before. The editor now gets a warning naming the field and the record instead
of an information, and the same is logged with the level warning. The warning
is kept in the session of the editor like the warning about a lost link, so it
is shown after the localization wizard and after a redirect as well. Both
warnings name the record the field belongs to, for a file reference or another
inline child localized along with its parent the child record.

TYPO3 Core patch may be required (l10n_source)
----------------------------------------------

Localizing a record whose table declares a translation source field
(``l10n_source``) - for example content elements built with ``MASK`` - can create
a **duplicate translation** in the same language. This is the root cause of the
duplicated translations reported for ``web-vision/deepltranslate-auto-renew`` in
`#42 <https://github.com/web-vision/deepltranslate-auto-renew/issues/42>`__.

The defect is in the TYPO3 Core, not in this extension:
``BackendUtility::getRecordLocalization()`` matches existing translations by
``l10n_source`` when the table defines one and does not fall back to
``l10n_parent``. A valid translation created without populating ``l10n_source``
(the usual result of a plain DataHandler datamap, an importer, a migration or
``MASK``) is not found, so ``DataHandler::localize()`` creates a second one.

The fix is upstream: `forge #110281 <https://forge.typo3.org/issues/110281>`__,
`Gerrit 94915 (13.4) <https://review.typo3.org/c/Packages/TYPO3.CMS/+/94915>`__
and released with TYPO3 **v13.4.34**. This extension requires at least that
version on the TYPO3 v13 side, so **nothing has to be done** on TYPO3 v13.

TYPO3 **v12.4 has reached ELTS and never receives the fix**. Instances on
TYPO3 v12.4 have to apply the patch themselves.

No Composer patch is declared any more
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Earlier releases declared the patch in the ``extra.patches`` section of the
``composer.json`` of this extension. That declaration used the object form with
a ``source`` and a ``version`` key, which only
`vaimo/composer-patches <https://github.com/vaimo/composer-patches>`__
understands. Patch declarations are collected from installed dependencies as
well, so projects using
`cweagans/composer-patches <https://github.com/cweagans/composer-patches>`__
aborted their Composer run with an "Array to string conversion" error (1.7.3) or
a type error in ``ResolverBase`` (2.0.0). See
`deepltranslate-core#646 <https://github.com/web-vision/deepltranslate-core/issues/646>`__.

The extension therefore **no longer declares or applies** any Composer patch.
The patch file below ``Documentation/CorePatches/`` stays in the repository as
documentation.

Applying the patch in a project
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Copy ``Documentation/CorePatches/typo3-cms-backend-110281-v12-v13.patch`` from
`web-vision/deepltranslate-core <https://github.com/web-vision/deepltranslate-core>`__
into the project - for example into its ``patches/`` directory - and declare it
for ``typo3/cms-backend``.

With ``cweagans/composer-patches`` the patch is declared as a plain path or URL
string:

..  code-block:: json

    {
        "extra": {
            "patches": {
                "typo3/cms-backend": {
                    "TYPO3 #110281 l10n_source": "patches/typo3-cms-backend-110281-v12-v13.patch"
                }
            }
        }
    }

``vaimo/composer-patches`` additionally understands the object form, which can
scope a patch to the affected TYPO3 versions:

..  code-block:: json

    {
        "extra": {
            "patches": {
                "typo3/cms-backend": {
                    "TYPO3 #110281 l10n_source": {
                        "source": "patches/typo3-cms-backend-110281-v12-v13.patch",
                        "version": ">=12.4.0 <13.0.0"
                    }
                }
            }
        }
    }

Patches the project applies to ``typo3/cms-backend`` itself may need adoption:
they have to apply on top of the changes shown below, otherwise patching fails
and aborts the Composer run. The same is true for patches provided by other
extensions for the same file.

See the general TYPO3 documentation on applying Composer patches for
project-specific setup details. The patch shipped by this extension:

..  literalinclude:: ../CorePatches/typo3-cms-backend-110281-v12-v13.patch
    :language: diff
    :caption: typo3-cms-backend-110281-v12-v13.patch
