..  _bugfix-translate-titles-and-alternative-texts-1790970336:

====================================================================
Bugfix: Titles, alternative texts and labels in rich text translated
====================================================================


Description
===========

DeepL translates the content of elements, never the values of their
attributes. The title an editor enters for a link in the rich text editor is
stored as the `title` attribute of the `<a>`, so it stayed in the source
language while the link text was translated (issue
`#427 <https://github.com/web-vision/deepltranslate-core/issues/427>`__).
Readers see the title as a tooltip and screen readers read it out.

The values of the attributes readers see or hear as text are now sent to DeepL
as texts of their own, in the same request as the field:

*   `title` of any element, for example of a link or of an abbreviation,
    `<abbr title="World Health Organization">WHO</abbr>`,
*   `alt` of an image,
*   `aria-label` of any element.

The translations replace the values in the translated field. DeepL keeps the
attributes of every element, also when it moves an element within the
sentence, so every link gets the translation of its own title. Equal values
are sent once.

A value is not translated in content marked as not to be translated, with
`translate="no"` or the class `notranslate` on the element or an ancestor, on
`<script>` and `<style>`, and if it has no letter, like a number. A value DeepL
returns unchanged, like a name, is kept as stored.

Impact
======

Translated rich text has translated tooltips, alternative texts and labels.
The characters of these values are billed by DeepL like the rest of the field,
and the glossary and formality of the translation apply to them as well. A
title holding something that must not change, like a product code, is sent
too. Marked with the class `notranslate`, the element keeps its title and its
text.

See pull request `#644 <https://github.com/web-vision/deepltranslate-core/pull/644>`__.
