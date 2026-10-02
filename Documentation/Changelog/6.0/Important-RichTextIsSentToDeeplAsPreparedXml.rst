..  _important-rich-text-is-sent-to-deepl-as-prepared-xml-1790945955:

=====================================================
Important: Rich text is sent to DeepL as prepared XML
=====================================================


Description
===========

Rich text fields are sent to DeepL as well-formed XML with
`tag_handling=xml` and `tag_handling_version=v2` again, instead of HTML. With
HTML tag handling DeepL merged links that touch each other into one link
(issue `#665 <https://github.com/web-vision/deepltranslate-core/issues/665>`__)
and put commas and periods at the start of lines separated by `<br>`
(issue `#642 <https://github.com/web-vision/deepltranslate-core/issues/642>`__).

Before the content is sent, it is prepared for the way DeepL places inline
markup, and the preparation is reverted in the translation:

*   Inline elements such as `<a>`, `<em>` or `<strong>` are sent as
    `non_splitting_tags`, so a sentence is translated as a whole across its
    markup. Without that, the main clause around a formatted link was lost
    (issue `#489 <https://github.com/web-vision/deepltranslate-core/issues/489>`__).
    This changes how DeepL splits every rich text sentence with inline markup
    into segments. Copies of an element DeepL makes are repaired where they
    are empty or adjacent, copies on separate words are kept.

*   Inline elements touching each other within a word, like a row of buttons,
    are translated one by one, so they stay separate links with their own
    target, class and title.

*   `<sup>` and `<sub>` holding digits or a symbol stay on the word they belong
    to, for example `m<sup>2</sup>`, `H<sub>2</sub>O`, `2021<sup>1</sup>` and
    `FOOBAR<sup>®</sup>` (issue `#311
    <https://github.com/web-vision/deepltranslate-core/issues/311>`__).

*   A word touching an inline element is sent with a space and glued again
    only where the translation kept both words, so translated compounds such
    as `Produkt<strong>neuheiten</strong>` are not fused.

*   Characters XML does not allow, such as the manual line break of word
    processors (U+000B), processing instructions and invalid comments, are
    removed or replaced, so DeepL does not reject the field (issue `#507
    <https://github.com/web-vision/deepltranslate-core/issues/507>`__).

The preparation adds a short reference number to every inline element of the
request. Tags are not billed, but they count for the request size limit of
DeepL (128 KiB). For the rich text of the development instances the request
grows by about 12 %, for a paragraph dense with inline markup by about 45 %.

Impact
======

Editors get translations that keep links, line breaks, formatting and
superscripts where the source has them. Some results differ from before:

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
    and is shown with the next backend page.

Integrators who replace
:php:`\WebVision\Deepltranslate\Core\Service\HtmlXmlConverterInterface` have to
implement the new tag options and return the lost links, see
:php:`\WebVision\Deepltranslate\Core\Service\ConvertedHtml`. Callers of
:php:`\WebVision\Deepltranslate\Core\Service\DeeplService::translateContent()`
find the lost links in
:php:`\WebVision\Deepltranslate\Core\Domain\Dto\TranslateContext::getLostLinks()`.

See issues `#489 <https://github.com/web-vision/deepltranslate-core/issues/489>`__,
`#642 <https://github.com/web-vision/deepltranslate-core/issues/642>`__,
`#665 <https://github.com/web-vision/deepltranslate-core/issues/665>`__ and
`#311 <https://github.com/web-vision/deepltranslate-core/issues/311>`__.
