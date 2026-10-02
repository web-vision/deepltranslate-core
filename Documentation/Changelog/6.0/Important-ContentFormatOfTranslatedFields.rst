..  _important-content-format-of-translated-fields-1790946717:

==================================================
Important: Content format of the translated fields
==================================================


Description
===========

Rich text is sent to DeepL as XML and converted back, plain text is escaped
before and decoded after the translation. Which way a field takes is its
content format, :php:`\WebVision\Deepltranslate\Core\Domain\Enum\ContentFormat`,
resolved from the TCA of the field by
:php:`\WebVision\Deepltranslate\Core\Service\ContentFormatResolver`.

Fields of inline children
-------------------------

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
------------------

HTML edited in a code editor (`renderType` `codeEditor` with the format
`html`, the default), for example the content element "Plain HTML" (CType
`html`), is translated like rich text. The content of `<script>` and `<style>`
is sent with `ignore_tags` and kept byte by byte, `<`, `&&` and `-->` included.
Without it, the XML tag handling lets DeepL translate strings in them, for
example `var label = "Opening hours"` becomes `var label = "Öffnungszeiten"`. Attribute
values such as `title`, `aria-label` or `data-*` are not translated, as before.
The content is parsed as HTML, so it is written back in normalized form, for
example `<br />`, quoted attributes and characters instead of named entities,
and comments with `--` inside are made valid XML, see
:ref:`important-rich-text-is-sent-to-deepl-as-prepared-xml-1790945955`.

Code in other formats, for example TypoScript, is not sent to DeepL. The
translation keeps the code of the source record unchanged.

Attributes
----------

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
--------------------------------

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
----------------

The translated rich text is written the way TYPO3 stores it: void elements as
`<br />`, `<hr />` and `<img … />`, and `<` and `>` in attribute values escaped
as `&lt;` and `&gt;`, unless the source writes the attributes of the element
differently, see above. A raw `>` in a `title` cuts the link apart in the
frontend, and an `<hr>` on its own line is removed when the translation is saved
through the rich text transformation. Line breaks are stored as LF instead of
CRLF.

Failed translations
-------------------

A field which cannot be translated keeps the text of the source language, as
before. The editor now gets a warning naming the field and the record instead
of an information, and the same is logged with the level warning. The warning
is kept in the session of the editor like the warning about a lost link, so it
is shown after the localization wizard and after a redirect as well. Both
warnings name the record the field belongs to, for a file reference or another
inline child localized along with its parent the child record.

Impact
======

Translations of file references and other inline children keep `<`, `&` and
quotes of plain text fields and the markup of rich text fields.

Translations of content elements of the type "Plain HTML" get their text
translated, their scripts and styles unchanged.

Related
=======

*   :ref:`important-rich-text-is-sent-to-deepl-as-prepared-xml-1790945955`
*   :ref:`important-inline-child-records-are-translated-through-their-parent-1785623102`
