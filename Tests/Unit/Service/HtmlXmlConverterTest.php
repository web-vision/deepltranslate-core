<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Random\Engine\Mt19937;
use Random\Randomizer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WebVision\Deepltranslate\Core\Exception\XmlConversionException;
use WebVision\Deepltranslate\Core\Service\HtmlXmlConverter;
use WebVision\Deepltranslate\Core\Service\LostLink;
use WebVision\Deepltranslate\Core\Service\RichTextHtml5;

#[CoversClass(HtmlXmlConverter::class)]
final class HtmlXmlConverterTest extends UnitTestCase
{
    /**
     * Exact markup reported in https://github.com/web-vision/deepltranslate-core/issues/665
     */
    private const ISSUE_665_HTML = '<p>' . "\n"
        . '    <a class="button button--whatsapp" href="https://api.whatsapp.com/send?phone=123456789" title="Ferrari 296 GTB mieten Dubai Whatsapp ">Whatsapp </a>'
        . '<a class="button button--call" href="tel:+971543946661" title="Ferrari 296 GTB mieten Dubai Anruf">Call</a>'
        . '<a class="button button--offerttool" href="t3://page?uid=407" title="Ferrari 296 GTB mieten Dubai Buchen">Book</a>' . "\n"
        . '</p>';

    /**
     * Exact markup reported in https://github.com/web-vision/deepltranslate-core/issues/642
     */
    private const ISSUE_642_HTML = '<p>' . "\n"
        . '    Postal address:<br>' . "\n"
        . '    P.O. Box 1234<br>' . "\n"
        . '    12345 Sample City<br>' . "\n"
        . '    <br>' . "\n"
        . '    Office address:<br>' . "\n"
        . '    Sample Building<br>' . "\n"
        . '    Sample Street 1<br>' . "\n"
        . '    12345 Sample City<br>' . "\n"
        . '</p>';

    /**
     * Exact markup reported in https://github.com/web-vision/deepltranslate-core/issues/489
     */
    private const ISSUE_489_HTML = '<p>' . "\n"
        . '    <i>Mehr über mögliche Einsatzbereiche zeigt Ihnen gerne unsere </i><a href="t3://page?uid=21#5017"><i>Studienberatung</i></a><i> auf.</i>' . "\n"
        . '</p>';

    public static function htmlToXmlDataProvider(): \Generator
    {
        yield 'empty content' => [
            'html' => '',
            'expectedXml' => '',
        ];
        yield 'plain text without markup' => [
            'html' => 'Unsere Leistungen im Überblick',
            'expectedXml' => 'Unsere Leistungen im Überblick',
        ];
        yield 'plain text with ampersand and less-than sign' => [
            'html' => 'Preise & Leistungen für Kinder < 12 Jahre',
            'expectedXml' => 'Preise &amp; Leistungen für Kinder &lt; 12 Jahre',
        ];
        yield 'void elements are self-closed' => [
            'html' => '<p>Zeile 1<br>Zeile 2</p><hr><img src="t3://file?uid=5" alt="Bild">',
            'expectedXml' => '<p>Zeile 1<br/>Zeile 2</p><hr/><img src="t3://file?uid=5" alt="Bild"/>',
        ];
        yield 'code in script is escaped as XML text' => [
            'html' => '<script>if (a < 768 && b > 0) {} // guard --></script>',
            'expectedXml' => '<script>if (a &lt; 768 &amp;&amp; b &gt; 0) {} // guard --&gt;</script>',
        ];
        yield 'named entity nbsp becomes the character' => [
            'html' => '<p>Preis:&nbsp;100&nbsp;€</p>',
            'expectedXml' => "<p>Preis:\u{A0}100\u{A0}€</p>",
        ];
        yield 'issue 642 markup' => [
            'html' => self::ISSUE_642_HTML,
            'expectedXml' => str_replace('<br>', '<br/>', self::ISSUE_642_HTML),
        ];
        yield 'issue 665 markup, touching links become splitting elements' => [
            'html' => self::ISSUE_665_HTML,
            'expectedXml' => '<p>' . "\n"
                . '    <a class="button button--whatsapp" href="https://api.whatsapp.com/send?phone=123456789" title="Ferrari 296 GTB mieten Dubai Whatsapp " dlt-r="0">Whatsapp </a>'
                . '<dlt-s class="button button--call" href="tel:+971543946661" title="Ferrari 296 GTB mieten Dubai Anruf" dlt-r="1">Call</dlt-s>'
                . '<dlt-s class="button button--offerttool" href="t3://page?uid=407" title="Ferrari 296 GTB mieten Dubai Buchen" dlt-r="2">Book</dlt-s>' . "\n"
                . '</p>',
        ];
        yield 'issue 489 markup, inline elements get reference numbers' => [
            'html' => self::ISSUE_489_HTML,
            'expectedXml' => '<p>' . "\n"
                . '    <i dlt-r="0">Mehr über mögliche Einsatzbereiche zeigt Ihnen gerne unsere </i><a href="t3://page?uid=21#5017" dlt-r="1"><i dlt-r="2">Studienberatung</i></a><i dlt-r="3"> auf.</i>' . "\n"
                . '</p>',
        ];
        yield 'word touching an inline element gets a space' => [
            'html' => '<p>Our<strong>new</strong>offer, e<em>Mail</em>, (<em>Name</em>).</p>',
            'expectedXml' => '<p>Our <strong dlt-r="0">new</strong> offer, e <em dlt-r="1">Mail</em>, (<em dlt-r="2">Name</em>).</p>',
        ];
        yield 'digits in sup and sub become script digits' => [
            'html' => '<p>Hello1<sup>2</sup>, 25 m<sup>2</sup> of H<sub>2</sub>O</p>',
            'expectedXml' => '<p>Hello1², 25 m² of H₂O</p>',
        ];
        yield 'symbol in sup becomes a placeholder' => [
            'html' => '<p>Try FOOBAR<sup>®</sup> now</p>',
            'expectedXml' => '<p>Try FOOBAR<dlt-p dlt-r="0"/> now</p>',
        ];
        yield 'digits in sup become a placeholder if the content has script digits already' => [
            'html' => '<p>10² and m<sup>2</sup></p>',
            'expectedXml' => '<p>10² and m<dlt-p dlt-r="0"/></p>',
        ];
        yield 'sup with letters stays an element' => [
            'html' => '<p>The 1<sup>st</sup> floor</p>',
            'expectedXml' => '<p>The 1 <sup dlt-r="0">st</sup> floor</p>',
        ];
        yield 'a line feed before an inline element is no glue' => [
            'html' => "<p>See the following\n<a href=\"t3://page?uid=1\">link</a></p>",
            'expectedXml' => "<p>See the following\n<a href=\"t3://page?uid=1\" dlt-r=\"0\">link</a></p>",
        ];
        yield 'content using helper names is sent without preparation' => [
            'html' => '<p>a<dlt-p dlt-r="0"></dlt-p>b <em>c</em>d</p>',
            'expectedXml' => '<p>a<dlt-p dlt-r="0"/>b <em>c</em>d</p>',
        ];
        yield 'characters XML does not allow, processing instructions and invalid comments' => [
            'html' => "<p title=\"a\u{1}b\">Zeile 1\u{B}Zeile 2\u{C}Seite 2\u{1}&#xFFFE;<?xml version=\"1.0\"?><!-- a -- b ---></p>",
            'expectedXml' => '<p title="ab">Zeile 1 Zeile 2 Seite 2<!-- a - - b - --></p>',
        ];
        yield 'plain text with control characters' => [
            'html' => "Zeile 1\u{B}Zeile 2\u{1F}",
            'expectedXml' => 'Zeile 1 Zeile 2',
        ];
    }

    #[Test]
    #[DataProvider('htmlToXmlDataProvider')]
    public function htmlToXmlReturnsWellFormedXml(string $html, string $expectedXml): void
    {
        $xml = (new HtmlXmlConverter())->htmlToXml($html);

        $this->assertSame($expectedXml, $xml);
        $this->assertWellFormedXml($xml);
    }

    public static function roundTripDataProvider(): \Generator
    {
        yield 'empty content' => [
            'html' => '',
            'expectedHtml' => '',
        ];
        yield 'plain text without markup' => [
            'html' => 'Unsere Leistungen im Überblick',
            'expectedHtml' => 'Unsere Leistungen im Überblick',
        ];
        yield 'plain text keeps leading and trailing whitespace' => [
            'html' => '  Text mit Leerzeichen  ',
            'expectedHtml' => '  Text mit Leerzeichen  ',
        ];
        yield 'plain text with ampersand and less-than sign is returned HTML escaped' => [
            'html' => 'Preise & Leistungen für Kinder < 12 Jahre',
            'expectedHtml' => 'Preise &amp; Leistungen für Kinder &lt; 12 Jahre',
        ];
        yield 'plain text with less-than signs not starting a tag' => [
            'html' => 'a <3 b <= c <',
            'expectedHtml' => 'a &lt;3 b &lt;= c &lt;',
        ];
        yield 'plain text keeps line breaks, normalized to LF like any HTML5 or XML parser does' => [
            'html' => "Zeile 1\nZeile 2\r\nZeile 3",
            'expectedHtml' => "Zeile 1\nZeile 2\nZeile 3",
        ];
        yield 'escaped markup in text stays escaped' => [
            'html' => '<p>Nutzen Sie &lt;br&gt; für Umbrüche &amp; mehr</p>',
            'expectedHtml' => '<p>Nutzen Sie &lt;br&gt; für Umbrüche &amp; mehr</p>',
        ];
        yield 'nbsp entity is kept as nbsp entity' => [
            'html' => '<p>Preis:&nbsp;100&nbsp;€</p>',
            'expectedHtml' => '<p>Preis:&nbsp;100&nbsp;€</p>',
        ];
        yield 'other named entities become characters' => [
            'html' => '<p>&copy; 2026 Muster&nbsp;GmbH &euro;</p>',
            'expectedHtml' => '<p>© 2026 Muster&nbsp;GmbH €</p>',
        ];
        yield 'attributes including t3 links, data and quotes are kept' => [
            'html' => '<p><a href="t3://page?uid=12#c34" class="link link--intern" title="Mehr &quot;erfahren&quot;" data-foo="bar" target="_blank">Link</a>'
                . ' <a href="https://example.com/?a=1&amp;b=2">Extern</a></p>',
            'expectedHtml' => '<p><a href="t3://page?uid=12#c34" class="link link--intern" title="Mehr &quot;erfahren&quot;" data-foo="bar" target="_blank">Link</a>'
                . ' <a href="https://example.com/?a=1&amp;b=2">Extern</a></p>',
        ];
        yield 'attribute values with less-than, greater-than, ampersand and quotes keep their value and stay escaped' => [
            'html' => '<p><a href="t3://page?uid=1&amp;L=2" title="a &lt; b &gt; c &amp; &quot;d&quot; \'e\'">x</a></p>',
            'expectedHtml' => '<p><a href="t3://page?uid=1&amp;L=2" title="a &lt; b &gt; c &amp; &quot;d&quot; \'e\'">x</a></p>',
        ];
        yield 'void elements are written the way TYPO3 stores them' => [
            'html' => '<p>Zeile 1<br>Zeile 2</p><hr><p><img src="t3://file?uid=5" alt="Ein Bild" width="300" height="200"></p>',
            'expectedHtml' => '<p>Zeile 1<br />Zeile 2</p><hr /><p><img src="t3://file?uid=5" alt="Ein Bild" width="300" height="200" /></p>',
        ];
        yield 'attribute names XML does not allow, Alpine.js' => [
            'html' => '<div x-data="{ open: false }" @click="open = !open" x-on:keydown.escape="open = false" :class="{ \'is-open\': open }"><p>Menu</p></div>',
            'expectedHtml' => '<div x-data="{ open: false }" @click="open = !open" x-on:keydown.escape="open = false" :class="{ \'is-open\': open }"><p>Menu</p></div>',
        ];
        yield 'attribute names XML does not allow, Vue and Angular' => [
            'html' => '<a v-bind:href="url" #ref="link" v-on:click.prevent="go()">Go</a> <button [disabled]="isOff" (click)="run()" @keyup.enter="run">Run</button>',
            'expectedHtml' => '<a v-bind:href="url" #ref="link" v-on:click.prevent="go()">Go</a> <button [disabled]="isOff" (click)="run()" @keyup.enter="run">Run</button>',
        ];
        yield 'inline event handler with less-than and ampersands' => [
            'html' => '<button onclick="if (a < b && c > 0) { alert(\'Hi\'); }">Click</button>',
            'expectedHtml' => '<button onclick="if (a < b && c > 0) { alert(\'Hi\'); }">Click</button>',
        ];
        yield 'uppercase, mixed-case and duplicate attribute names' => [
            'html' => '<p DATA-Foo="1" onClick="x()" Title="Hello" class="a" class="b">Text</p>',
            'expectedHtml' => '<p DATA-Foo="1" onClick="x()" Title="Hello" class="a" class="b">Text</p>',
        ];
        yield 'attribute values as written in the source' => [
            'html' => "<p><a href=\"https://example.com/?a=1&b=2\" title='single' data-x=unquoted>Link</a><img src=\"x.jpg\" alt=\"\"></p>",
            'expectedHtml' => "<p><a href=\"https://example.com/?a=1&b=2\" title='single' data-x=unquoted>Link</a><img src=\"x.jpg\" alt=\"\" /></p>",
        ];
        yield 'last attribute without value' => [
            'html' => '<p class=>Text</p><button @click="go" class= >Go</button>',
            'expectedHtml' => '<p class=>Text</p><button @click="go" class=>Go</button>',
        ];
        yield 'helper attribute in upper case, the content is sent without preparation' => [
            'html' => '<p><a href="1">Call</a><a href="2" DLT-A="0">Book</a></p>',
            'expectedHtml' => '<p><a href="1">Call</a><a href="2" dlt-a="0">Book</a></p>',
        ];
        yield 'line breaks between attributes' => [
            'html' => "<div\n  class=\"teaser\"\n  data-id=\"5\"\n><p>Text</p></div>",
            'expectedHtml' => "<div\n  class=\"teaser\"\n  data-id=\"5\"><p>Text</p></div>",
        ];
        yield 'srcset, sizes and boolean attributes' => [
            'html' => '<img src="a.jpg" srcset="a.jpg 1x, a@2x.jpg 2x" sizes="(max-width: 600px) 480px, 800px" alt="A picture"><input type="checkbox" checked disabled><details open><summary>More</summary>Text</details>',
            'expectedHtml' => '<img src="a.jpg" srcset="a.jpg 1x, a@2x.jpg 2x" sizes="(max-width: 600px) 480px, 800px" alt="A picture" /><input type="checkbox" checked disabled /><details open><summary>More</summary>Text</details>',
        ];
        yield 'svg with case-sensitive attribute names' => [
            'html' => '<p><svg viewBox="0 0 10 10"><linearGradient gradientUnits="userSpaceOnUse" id="g"></linearGradient><path d="M0 0"/></svg></p>',
            // An empty SVG element is written self-closed, as before.
            'expectedHtml' => '<p><svg viewBox="0 0 10 10"><linearGradient gradientUnits="userSpaceOnUse" id="g" /><path d="M0 0" /></svg></p>',
        ];
        yield 'template and noscript' => [
            'html' => '<template id="row"><p class="row" @click="pick()">Row</p></template><noscript><p>Please enable JavaScript.</p></noscript>',
            'expectedHtml' => '<template id="row"><p class="row" @click="pick()">Row</p></template><noscript><p>Please enable JavaScript.</p></noscript>',
        ];
        yield 'comment inside a script' => [
            'html' => '<script><!-- if (a < b) { x = "<p>"; } --></script><p>Text</p>',
            'expectedHtml' => '<script><!-- if (a < b) { x = "<p>"; } --></script><p>Text</p>',
        ];
        yield 'script without end tag gets one, its code stays as it is' => [
            'html' => '<p>Text</p><script>var a = 1 < 2 && "<b>";',
            'expectedHtml' => '<p>Text</p><script>var a = 1 < 2 && "<b>";</script>',
        ];
        yield 'code in script and style stays as it is' => [
            'html' => '<div class="hours"><p>Opening hours</p></div><style>.hours > p::after { content: "Open"; }</style>'
                . '<script>if (a < 768 && b > 0) { label = "Opening hours"; } // guard --></script>',
            'expectedHtml' => '<div class="hours"><p>Opening hours</p></div><style>.hours > p::after { content: "Open"; }</style>'
                . '<script>if (a < 768 && b > 0) { label = "Opening hours"; } // guard --></script>',
        ];
        yield 'void elements stored by TYPO3' => [
            'html' => "<p>Zeile 1<br />Zeile 2</p>\n<hr />\n<p>Text</p>",
            'expectedHtml' => "<p>Zeile 1<br />Zeile 2</p>\n<hr />\n<p>Text</p>",
        ];
        yield 'less-than and greater-than signs in attribute values stay escaped' => [
            'html' => '<p><a href="t3://page?uid=1" title="Home &gt; Products &lt; &amp; &quot;more&quot;">Products</a></p>',
            'expectedHtml' => '<p><a href="t3://page?uid=1" title="Home &gt; Products &lt; &amp; &quot;more&quot;">Products</a></p>',
        ];
        yield 'comments' => [
            'html' => '<!-- Hinweis für Redakteure --><p>Text</p>',
            'expectedHtml' => '<!-- Hinweis für Redakteure --><p>Text</p>',
        ];
        yield 'comments XML does not allow are made valid' => [
            'html' => '<p>Text<!-- alt -- neu --><!---x---></p>',
            'expectedHtml' => '<p>Text<!-- alt - - neu --><!---x- --></p>',
        ];
        yield 'characters XML does not allow are replaced or removed, processing instructions removed' => [
            'html' => "<p title=\"a\u{B}b\">Zeile 1\u{B}Zeile 2\u{C}Seite 2\u{1}\u{FFFF}<?php echo 1; ?></p>",
            'expectedHtml' => '<p title="a b">Zeile 1 Zeile 2 Seite 2</p>',
        ];
        yield 'nested inline markup' => [
            'html' => '<p>Wir bieten <strong>schnelle <em>und <a href="t3://page?uid=3">kostenlose</a></em></strong> Lieferung.</p>',
            'expectedHtml' => '<p>Wir bieten <strong>schnelle <em>und <a href="t3://page?uid=3">kostenlose</a></em></strong> Lieferung.</p>',
        ];
        yield 'nested glued inline markup' => [
            'html' => '<p>Our<strong>new<em>big</em></strong>offer, e<strong>Mail</strong> and <em>i</em>Phone</p>',
            'expectedHtml' => '<p>Our<strong>new<em>big</em></strong>offer, e<strong>Mail</strong> and <em>i</em>Phone</p>',
        ];
        yield 'nested lists' => [
            'html' => "<ul>\n<li>Eins\n<ol><li>Unterpunkt</li></ol></li>\n<li>Zwei</li>\n</ul>",
            'expectedHtml' => "<ul>\n<li>Eins\n<ol><li>Unterpunkt</li></ol></li>\n<li>Zwei</li>\n</ul>",
        ];
        yield 'table' => [
            'html' => '<figure class="table"><table class="contenttable"><thead><tr><th scope="col">Name</th><th>Ort</th></tr></thead>'
                . '<tbody><tr><td>Max Mustermann</td><td colspan="2">Musterstadt</td></tr></tbody></table></figure>',
            'expectedHtml' => '<figure class="table"><table class="contenttable"><thead><tr><th scope="col">Name</th><th>Ort</th></tr></thead>'
                . '<tbody><tr><td>Max Mustermann</td><td colspan="2">Musterstadt</td></tr></tbody></table></figure>',
        ];
        yield 'empty elements are not collapsed' => [
            'html' => '<p></p><p>&nbsp;</p><p><em></em> <strong> </strong><a href="#"></a><span class="icon"></span>x<sup></sup><sub> </sub></p>',
            'expectedHtml' => '<p></p><p>&nbsp;</p><p><em></em> <strong> </strong><a href="#"></a><span class="icon"></span>x<sup></sup><sub> </sub></p>',
        ];
        yield 'issue 642 markup' => [
            'html' => self::ISSUE_642_HTML,
            'expectedHtml' => str_replace('<br>', '<br />', self::ISSUE_642_HTML),
        ];
        yield 'issue 665 markup' => [
            'html' => self::ISSUE_665_HTML,
            'expectedHtml' => self::ISSUE_665_HTML,
        ];
        yield 'issue 489 markup' => [
            'html' => self::ISSUE_489_HTML,
            'expectedHtml' => self::ISSUE_489_HTML,
        ];
        yield 'adjacent links in running text and a list' => [
            'html' => '<p>Reach us by <a href="https://api.whatsapp.com/send?phone=123456789">Whatsapp</a><a href="tel:+491234567">phone</a> or on the <a href="t3://page?uid=40">contact page</a>.</p>'
                . '<ul><li><strong><a href="t3://page?uid=40">Contact</a></strong><em><a href="t3://page?uid=10">Home</a></em></li></ul>',
            'expectedHtml' => '<p>Reach us by <a href="https://api.whatsapp.com/send?phone=123456789">Whatsapp</a><a href="tel:+491234567">phone</a> or on the <a href="t3://page?uid=40">contact page</a>.</p>'
                . '<ul><li><strong><a href="t3://page?uid=40">Contact</a></strong><em><a href="t3://page?uid=10">Home</a></em></li></ul>',
        ];
        yield 'words glued to elements' => [
            'html' => '<p>Try the new FOOBAR<sup>®</sup> now!</p><p>Our<strong>new</strong>offer, e<strong>Mail</strong> and 10<sup>3</sup>&nbsp;kg.</p>',
            'expectedHtml' => '<p>Try the new FOOBAR<sup>®</sup> now!</p><p>Our<strong>new</strong>offer, e<strong>Mail</strong> and 10<sup>3</sup>&nbsp;kg.</p>',
        ];
        yield 'sup and sub with digits, letters and attributes' => [
            'html' => '<p>Hello1<sup>2</sup>, H<sub>2</sub>O, the 1<sup>st</sup>, <sup>st</sup>, a<sup class="x">1</sup> and b<sup class="x">*</sup>.</p>',
            'expectedHtml' => '<p>Hello1<sup>2</sup>, H<sub>2</sub>O, the 1<sup>st</sup>, <sup>st</sup>, a<sup class="x">1</sup> and b<sup class="x">*</sup>.</p>',
        ];
        yield 'a literal script digit next to a digit sup' => [
            'html' => '<p>10² and m<sup>2</sup></p>',
            'expectedHtml' => '<p>10² and m<sup>2</sup></p>',
        ];
        yield 'touching elements with attributes in the XML namespace' => [
            'html' => '<p><span lang="en" xml:lang="en">Call</span><span xml:lang="de">Buchen</span></p>',
            'expectedHtml' => '<p><span lang="en" xml:lang="en">Call</span><span xml:lang="de">Buchen</span></p>',
        ];
        yield 'text the helper elements use as names' => [
            'html' => '<p>&lt;dlt-s&gt; and dlt-r="1"</p>',
            'expectedHtml' => '<p>&lt;dlt-s&gt; and dlt-r="1"</p>',
        ];
        yield 'helper elements and attributes in the content' => [
            'html' => '<p>a<dlt-p dlt-r="0" markup="&lt;b&gt;x&lt;/b&gt;"></dlt-p>b</p><p><span dlt-r="5">a</span><span dlt-g="x">b</span> c</p><div dlt-r="0"></div><p><em>x</em></p>',
            'expectedHtml' => '<p>a<dlt-p dlt-r="0" markup="&lt;b&gt;x&lt;/b&gt;"></dlt-p>b</p><p><span dlt-r="5">a</span><span dlt-g="x">b</span> c</p><div dlt-r="0"></div><p><em>x</em></p>',
        ];
        yield 'a line feed before an inline element' => [
            'html' => "<p>See the following\n<a href=\"t3://page?uid=1\">link</a>\nnow and\r\n<strong>bold</strong> words</p>",
            'expectedHtml' => "<p>See the following\n<a href=\"t3://page?uid=1\">link</a>\nnow and\n<strong>bold</strong> words</p>",
        ];
        yield 'a code listing with highlighted words' => [
            'html' => "<pre><code>const a = 1\n<span class=\"k\">return</span> a\n</code></pre>",
            'expectedHtml' => "<pre><code>const a = 1\n<span class=\"k\">return</span> a\n</code></pre>",
        ];
        yield 'letter tokens and compounds' => [
            'html' => '<p>e<strong>Mail</strong>, i<em>Phone</em><sup>®</sup>, Produkt<strong>neuheiten</strong>, <strong>Versand</strong>kosten, x<sup>n</sup></p>',
            'expectedHtml' => '<p>e<strong>Mail</strong>, i<em>Phone</em><sup>®</sup>, Produkt<strong>neuheiten</strong>, <strong>Versand</strong>kosten, x<sup>n</sup></p>',
        ];
    }

    #[Test]
    #[DataProvider('roundTripDataProvider')]
    public function roundTripKeepsContentUntouchedByTranslation(string $html, string $expectedHtml): void
    {
        $subject = new HtmlXmlConverter();

        $xml = $subject->htmlToXml($html);

        $this->assertWellFormedXml($xml);
        $this->assertSame($expectedHtml, $subject->xmlToHtml($xml, $html)->html);
    }

    /**
     * The answers DeepL gave on 2026-10-01, with the options of {@see \WebVision\Deepltranslate\Core\Translator}.
     * The helper names of that time are replaced by the current ones, the text is DeepL's.
     */
    public static function recordedDeepLAnswerDataProvider(): \Generator
    {
        yield 'issue 489 to English, DeepL copies the italic element' => [
            'html' => '<p>' . "\n"
                . '    <i>Mehr über mögliche Einsatzbereiche zeigt Ihnen gerne unsere </i><a href="t3://page?uid=21#5017"><i>Studienberatung</i></a><i> auf.</i>' . "\n"
                . '</p>',
            'answer' => '<p>' . "\n"
                . '    <i dlt-r="0">Our </i><a href="t3://page?uid=21#5017" dlt-r="1"><i dlt-r="2">student advisory service</i></a><i dlt-r="3"></i>' . "\n"
                . '    <i dlt-r="0">will be happy to tell you more about possible career paths</i><i dlt-r="3">.</i>' . "\n"
                . '</p>',
            'expectedHtml' => '<p>' . "\n"
                . '    <i>Our </i><a href="t3://page?uid=21#5017"><i>student advisory service</i></a>' . "\n"
                . '    <i>will be happy to tell you more about possible career paths.</i>' . "\n"
                . '</p>',
        ];
        yield 'issue 489 to French, DeepL copies the italic element' => [
            'html' => '<p>' . "\n"
                . '    <i>Mehr über mögliche Einsatzbereiche zeigt Ihnen gerne unsere </i><a href="t3://page?uid=21#5017"><i>Studienberatung</i></a><i> auf.</i>' . "\n"
                . '</p>',
            'answer' => '<p>' . "\n"
                . '    <i dlt-r="0">Notre </i><a href="t3://page?uid=21#5017" dlt-r="1"><i dlt-r="2">service d\'orientation universitaire</i></a>' . "\n"
                . '    ' . "\n"
                . '    <i dlt-r="0">se fera un plaisir de vous en dire plus sur les débouchés professionnels possibles</i><i dlt-r="3">.</i>' . "\n"
                . '</p>',
            'expectedHtml' => '<p>' . "\n"
                . '    <i>Notre </i><a href="t3://page?uid=21#5017"><i>service d\'orientation universitaire</i></a>' . "\n"
                . '    ' . "\n"
                . '    <i>se fera un plaisir de vous en dire plus sur les débouchés professionnels possibles.</i>' . "\n"
                . '</p>',
        ];
        yield 'issue 665 markup to English' => [
            'html' => '<p>' . "\n"
                . '    <a class="button button--whatsapp" href="https://api.whatsapp.com/send?phone=123456789" title="Ferrari 296 GTB mieten Dubai Whatsapp ">Whatsapp </a><a class="button button--call" href="tel:+971543946661" title="Ferrari 296 GTB mieten Dubai Anruf">Call</a><a class="button button--offerttool" href="t3://page?uid=407" title="Ferrari 296 GTB mieten Dubai Buchen">Book</a>' . "\n"
                . '</p>',
            'answer' => '<p>' . "\n"
                . '    <a class="button button--whatsapp" href="https://api.whatsapp.com/send?phone=123456789" title="Ferrari 296 GTB mieten Dubai Whatsapp " dlt-r="0">WhatsApp </a><dlt-s class="button button--call" href="tel:+971543946661" title="Ferrari 296 GTB mieten Dubai Anruf" dlt-r="1">Call</dlt-s><dlt-s class="button button--offerttool" href="t3://page?uid=407" title="Ferrari 296 GTB mieten Dubai Buchen" dlt-r="2">Book</dlt-s>' . "\n"
                . '</p>',
            'expectedHtml' => '<p>' . "\n"
                . '    <a class="button button--whatsapp" href="https://api.whatsapp.com/send?phone=123456789" title="Ferrari 296 GTB mieten Dubai Whatsapp ">WhatsApp </a><a class="button button--call" href="tel:+971543946661" title="Ferrari 296 GTB mieten Dubai Anruf">Call</a><a class="button button--offerttool" href="t3://page?uid=407" title="Ferrari 296 GTB mieten Dubai Buchen">Book</a>' . "\n"
                . '</p>',
        ];
        yield 'issue 665, element 10002 to French' => [
            'html' => '<p><a class="button button--whatsapp" href="https://api.whatsapp.com/send?phone=123456789" title="Rent a sports car and ask on Whatsapp">Whatsapp </a><a class="button button--call" href="tel:+491234567" title="Rent a sports car and call us">Call</a><a class="button button--book" href="t3://page?uid=40" title="Rent a sports car and book it">Book</a></p>',
            'answer' => '<p><a class="button button--whatsapp" href="https://api.whatsapp.com/send?phone=123456789" title="Rent a sports car and ask on Whatsapp" dlt-r="0">WhatsApp </a><dlt-s class="button button--call" href="tel:+491234567" title="Rent a sports car and call us" dlt-r="1">Appeler</dlt-s><dlt-s class="button button--book" href="t3://page?uid=40" title="Rent a sports car and book it" dlt-r="2">Réserver</dlt-s></p>',
            'expectedHtml' => '<p><a class="button button--whatsapp" href="https://api.whatsapp.com/send?phone=123456789" title="Rent a sports car and ask on Whatsapp">WhatsApp </a><a class="button button--call" href="tel:+491234567" title="Rent a sports car and call us">Appeler</a><a class="button button--book" href="t3://page?uid=40" title="Rent a sports car and book it">Réserver</a></p>',
        ];
        yield 'adjacent links in running text and a list, element 10004 to German' => [
            'html' => '<p>Reach us by <a href="https://api.whatsapp.com/send?phone=123456789">Whatsapp</a><a href="tel:+491234567">phone</a><a href="mailto:hello@example.org">mail</a> or on the <a href="t3://page?uid=40">contact page</a>.</p>' . "\n"
                . '<ul>' . "\n"
                . '<li><a href="t3://page?uid=20">Features</a><a href="t3://page?uid=30">About</a></li>' . "\n"
                . '<li><strong><a href="t3://page?uid=40">Contact</a></strong><em><a href="t3://page?uid=10">Home</a></em></li>' . "\n"
                . '</ul>',
            'answer' => '<p>So erreichen Sie uns <dlt-s href="https://api.whatsapp.com/send?phone=123456789" dlt-r="0">WhatsApp</dlt-s><dlt-s href="tel:+491234567" dlt-r="1">Telefon</dlt-s><dlt-s href="mailto:hello@example.org" dlt-r="2">E-Mail</dlt-s> oder über die <a href="t3://page?uid=40" dlt-r="3">Kontaktseite</a>.</p>' . "\n"
                . '<ul>' . "\n"
                . '<li><dlt-s href="t3://page?uid=20" dlt-r="4">Funktionen</dlt-s><dlt-s href="t3://page?uid=30" dlt-r="5">Über</dlt-s></li>' . "\n"
                . '<li><dlt-s dlt-r="6"><a href="t3://page?uid=40" dlt-r="7">Kontakt</a></dlt-s><dlt-s dlt-r="8"><a href="t3://page?uid=10" dlt-r="9">Startseite</a></dlt-s></li>' . "\n"
                . '</ul>',
            'expectedHtml' => '<p>So erreichen Sie uns <a href="https://api.whatsapp.com/send?phone=123456789">WhatsApp</a><a href="tel:+491234567">Telefon</a><a href="mailto:hello@example.org">E-Mail</a> oder über die <a href="t3://page?uid=40">Kontaktseite</a>.</p>' . "\n"
                . '<ul>' . "\n"
                . '<li><a href="t3://page?uid=20">Funktionen</a><a href="t3://page?uid=30">Über</a></li>' . "\n"
                . '<li><strong><a href="t3://page?uid=40">Kontakt</a></strong><em><a href="t3://page?uid=10">Startseite</a></em></li>' . "\n"
                . '</ul>',
        ];
        yield 'issue 311, words glued to elements, element 13004 to French, answer of the glue step without token protection' => [
            'html' => '<p>Try the new FOOBAR<sup>®</sup> now!</p>' . "\n"
                . '<p>Our<strong>new</strong>offer starts today: the<em>extended</em>warranty, the free<a href="t3://page?uid=40">delivery</a>and the<u>personal</u>advice.</p>' . "\n"
                . '<p>Prefixes like e<strong>Mail</strong> and i<em>Phone</em> and units like 10<sup>3</sup>&nbsp;kg stay as they are.</p>',
            'answer' => '<p>Essayez dès maintenant le nouveau FOOBAR<dlt-p dlt-r="0"/> !</p>' . "\n"
                . '<p>Notre <strong dlt-r="1">nouvelle</strong> offre commence dès aujourd’hui : la garantie <em dlt-r="2">prolongée</em>, la <a href="t3://page?uid=40" dlt-r="3">livraison</a> gratuite et les conseils <u dlt-r="4">personnalisés</u>.</p>' . "\n"
                . '<p>Les préfixes tels que « e<strong dlt-r="5">-mail »</strong> et « <em dlt-r="6">iPhone »</em> ainsi que les unités telles que « 10³ kg » restent inchangés.</p>',
            'expectedHtml' => '<p>Essayez dès maintenant le nouveau FOOBAR<sup>®</sup> !</p>' . "\n"
                . '<p>Notre <strong>nouvelle</strong> offre commence dès aujourd’hui : la garantie <em>prolongée</em>, la <a href="t3://page?uid=40">livraison</a> gratuite et les conseils <u>personnalisés</u>.</p>' . "\n"
                . '<p>Les préfixes tels que « e<strong>-mail »</strong> et « <em>iPhone »</em> ainsi que les unités telles que « 10<sup>3</sup> kg » restent inchangés.</p>',
        ];
        yield 'DPL-89, sup and sub after digits and letters, element 13006 to German' => [
            'html' => '<p>Hello1<sup>2</sup>, this sentence has a footnote marker.</p>' . "\n"
                . '<p>The room has 25 m<sup>2</sup>, the tank holds 3 m<sup>3</sup> of water (H<sub>2</sub>O), and the formula is E = mc<sup>2</sup>.</p>' . "\n"
                . '<p>Footnote markers after numbers: 2021<sup>1</sup>, 2022<sup>2</sup> and 2023<sup>3</sup>.</p>',
            'answer' => '<p>Hallo1², dieser Satz enthält eine Fußnotenmarkierung.</p>' . "\n"
                . '<p>Der Raum ist 25 m² groß, der Tank fasst 3 m³ Wasser (H₂O) und die Formel lautet E = mc².</p>' . "\n"
                . '<p>Fußnotenzeichen hinter Zahlen: 2021¹, 2022² und 2023³.</p>',
            'expectedHtml' => '<p>Hallo1<sup>2</sup>, dieser Satz enthält eine Fußnotenmarkierung.</p>' . "\n"
                . '<p>Der Raum ist 25 m<sup>2</sup> groß, der Tank fasst 3 m<sup>3</sup> Wasser (H<sub>2</sub>O) und die Formel lautet E = mc<sup>2</sup>.</p>' . "\n"
                . '<p>Fußnotenzeichen hinter Zahlen: 2021<sup>1</sup>, 2022<sup>2</sup> und 2023<sup>3</sup>.</p>',
        ];
        yield 'issue 507, non-breaking spaces, element 12002 to German' => [
            'html' => '<h4>&nbsp;</h4>' . "\n"
                . '<p>The price is 100&nbsp;€ per month, the setup costs 50&nbsp;€ once.</p>' . "\n"
                . '<p>Call us on +49&nbsp;1234&nbsp;567 between 9&nbsp;a.m. and 5&nbsp;p.m.</p>',
            'answer' => "<h4>\u{A0}</h4>" . "\n"
                . '<p>Der Preis beträgt 100 € pro Monat, die Einrichtungsgebühr beträgt einmalig 50 €.</p>' . "\n"
                . '<p>Rufen Sie uns unter +49 1234 567 zwischen 9 und 17 Uhr an.</p>',
            'expectedHtml' => '<h4>&nbsp;</h4>' . "\n"
                . '<p>Der Preis beträgt 100 € pro Monat, die Einrichtungsgebühr beträgt einmalig 50 €.</p>' . "\n"
                . '<p>Rufen Sie uns unter +49 1234 567 zwischen 9 und 17 Uhr an.</p>',
        ];
        yield 'issue 278, spaces around inline elements, element 13002 to French' => [
            'html' => '<p>Important species in blueberry include the Western flower thrips (<em>Frankliniella occidentalis</em>) and Chilli thrips (<em>Scirtothrips dorsalis</em>).</p>' . "\n"
                . '<p>The parasitic wasps<em>&nbsp;Diglyphus</em>&nbsp;<em>isaea</em>&nbsp;(<a href="t3://page?uid=30">Miglyphus</a>) and<em>&nbsp;Dacnusa sibirica</em>&nbsp;(<a href="t3://page?uid=30">Minusa</a>), are effective natural enemies of leaf miner larvae.</p>',
            'answer' => '<p>Parmi les espèces importantes présentes sur les myrtilles, on peut citer le thrips occidental des fleurs (<em dlt-r="0">Frankliniella occidentalis</em>) et le thrips du piment (<em dlt-r="1">Scirtothrips dorsalis</em>).</p>' . "\n"
                . '<p>Les guêpes parasites <em dlt-r="2">Diglyphus</em> <em dlt-r="3">isaea</em> (<a href="t3://page?uid=30" dlt-r="4">Miglyphus</a>) et<em dlt-r="5"> Dacnusa sibirica</em> (<a href="t3://page?uid=30" dlt-r="6">Minusa</a>) sont des ennemis naturels efficaces des larves de mineuses.</p>',
            'expectedHtml' => '<p>Parmi les espèces importantes présentes sur les myrtilles, on peut citer le thrips occidental des fleurs (<em>Frankliniella occidentalis</em>) et le thrips du piment (<em>Scirtothrips dorsalis</em>).</p>' . "\n"
                . '<p>Les guêpes parasites <em>Diglyphus</em> <em>isaea</em> (<a href="t3://page?uid=30">Miglyphus</a>) et<em> Dacnusa sibirica</em> (<a href="t3://page?uid=30">Minusa</a>) sont des ennemis naturels efficaces des larves de mineuses.</p>',
        ];
        yield 'nested inline markup, element 13007 to German' => [
            'html' => '<p>Text with <strong>bold</strong>, <em>italic</em>, <strong><em>bold and italic</em></strong>, <u>underlined</u>, <s>struck</s>, <code>code</code>, and a <a href="t3://page?uid=20"><strong>bold link</strong> with <em>italic</em> text</a>.</p>' . "\n"
                . '<p><strong>Important: <em>read the <a href="t3://page?uid=40">terms</a> first</em>, then sign.</strong></p>',
            'answer' => '<p>Text mit <strong dlt-r="0">Fettdruck</strong>, <em dlt-r="1">Kursivschrift</em>, <strong dlt-r="2"><em dlt-r="3">Fett- und Kursivschrift</em></strong>, <u dlt-r="4">Unterstreichung</u>, <s dlt-r="5">Durchstreichung</s>, <code dlt-r="6">Code</code> sowie einem <a href="t3://page?uid=20" dlt-r="7"><strong dlt-r="8">fettgedruckten Link</strong> mit <em dlt-r="9">kursivem</em> Text</a>.</p>' . "\n"
                . '<p><strong dlt-r="10">Wichtig: <em dlt-r="11">Lies zuerst die <a href="t3://page?uid=40" dlt-r="12">Bedingungen</a> durch </em>und unterschreibe dann.</strong></p>',
            'expectedHtml' => '<p>Text mit <strong>Fettdruck</strong>, <em>Kursivschrift</em>, <strong><em>Fett- und Kursivschrift</em></strong>, <u>Unterstreichung</u>, <s>Durchstreichung</s>, <code>Code</code> sowie einem <a href="t3://page?uid=20"><strong>fettgedruckten Link</strong> mit <em>kursivem</em> Text</a>.</p>' . "\n"
                . '<p><strong>Wichtig: <em>Lies zuerst die <a href="t3://page?uid=40">Bedingungen</a> durch </em>und unterschreibe dann.</strong></p>',
        ];
        yield 'escaped markup, quotes and ampersand in attributes, element 12005 to French' => [
            'html' => '<p>Use &lt;b&gt; for bold text &amp; &lt;i&gt; for italic text in the old markup.</p>' . "\n"
                . '<p><a href="https://example.org/office?floor=1&amp;room=2" title="Learn &quot;everything&quot; about the office">Visit the office</a> and&nbsp;say hello.</p>',
            'answer' => '<p>Dans l\'ancien balisage, utilisez &lt;b&gt; pour le texte en gras et &lt;i&gt; pour le texte en italique.</p>' . "\n"
                . '<p><a href="https://example.org/office?floor=1&amp;room=2" title="Learn &quot;everything&quot; about the office" dlt-r="0">Passez au bureau</a> pour dire bonjour.</p>',
            'expectedHtml' => '<p>Dans l\'ancien balisage, utilisez &lt;b&gt; pour le texte en gras et &lt;i&gt; pour le texte en italique.</p>' . "\n"
                . '<p><a href="https://example.org/office?floor=1&amp;room=2" title="Learn &quot;everything&quot; about the office">Passez au bureau</a> pour dire bonjour.</p>',
        ];
    }

    #[Test]
    #[DataProvider('recordedDeepLAnswerDataProvider')]
    public function recordedDeepLAnswerIsReverted(string $html, string $answer, string $expectedHtml): void
    {
        $subject = new HtmlXmlConverter();

        $this->assertWellFormedXml($subject->htmlToXml($html));
        $this->assertSame($expectedHtml, $subject->xmlToHtml($answer, $html)->html);
    }

    /**
     * Answers in the shape DeepL gives, for content that was not sent to DeepL.
     */
    public static function simulatedDeepLAnswerDataProvider(): \Generator
    {
        yield 'sup with letters, glued to a digit' => [
            'html' => '<p>The 1<sup>st</sup> floor</p>',
            'answer' => '<p>Le 1 <sup dlt-r="0">er</sup> étage</p>',
            'expectedHtml' => '<p>Le 1<sup>er</sup> étage</p>',
        ];
        yield 'sup with a class' => [
            'html' => '<p>Footnote<sup class="x">1</sup>.</p>',
            'answer' => '<p>Fußnote<dlt-p dlt-r="0"/>.</p>',
            'expectedHtml' => '<p>Fußnote<sup class="x">1</sup>.</p>',
        ];
        yield 'a literal script digit next to a digit sup' => [
            'html' => '<p>10² and m<sup>2</sup></p>',
            'answer' => '<p>10² und m<dlt-p dlt-r="0"/></p>',
            'expectedHtml' => '<p>10² und m<sup>2</sup></p>',
        ];
        yield 'nested glued inline markup, translated words stay apart' => [
            'html' => '<p>Our<strong>new<em>big</em></strong>offer</p>',
            'answer' => '<p>Unser <strong dlt-r="0">neues <em dlt-r="1">großes</em></strong> Angebot</p>',
            'expectedHtml' => '<p>Unser <strong>neues <em>großes</em></strong> Angebot</p>',
        ];
        yield 'nested glued inline markup, untranslated words are glued again' => [
            'html' => '<p>Our<strong>new<em>big</em></strong>offer</p>',
            'answer' => '<p>Our <strong dlt-r="0">new <em dlt-r="1">big</em></strong> offer</p>',
            'expectedHtml' => '<p>Our<strong>new<em>big</em></strong>offer</p>',
        ];
        yield 'compounds of a German source translated to two words' => [
            'html' => '<p>Unsere Produkt<strong>neuheiten</strong> sind da, die <strong>Versand</strong>kosten sind niedrig.</p>',
            'answer' => '<p>Our product <strong dlt-r="0">news</strong> is here, the <strong dlt-r="1">shipping</strong> costs are low.</p>',
            'expectedHtml' => '<p>Our product <strong>news</strong> is here, the <strong>shipping</strong> costs are low.</p>',
        ];
        yield 'known limitation: a single styled letter DeepL pulled into the element' => [
            'html' => '<p>Das neue i<em>Phone</em> ist da.</p>',
            'answer' => '<p>Le nouvel <em dlt-r="0">iPhone</em> est sorti.</p>',
            'expectedHtml' => '<p>Le nouvel <em>iPhone</em> est sorti.</p>',
        ];
        yield 'a splitting element whose reference number DeepL dropped becomes its text, never an element of a guessed name' => [
            'html' => '<p><a href="1">Call</a><a href="2">Book</a></p>',
            'answer' => '<p><dlt-s href="1">Anruf</dlt-s><dlt-s href="2" dlt-r="1">Buchen</dlt-s></p>',
            'expectedHtml' => '<p>Anruf<a href="2">Buchen</a></p>',
        ];
        yield 'empty elements' => [
            'html' => '<p>Text<span class="icon"></span> mehr</p>',
            'answer' => '<p>Text<span class="icon" dlt-r="0"/> more</p>',
            'expectedHtml' => '<p>Text<span class="icon"></span> more</p>',
        ];
        yield 'attributes with less-than, greater-than, ampersand and quotes' => [
            'html' => '<p><a href="t3://page?uid=1&amp;L=2" title="a &lt; b &amp; &quot;c&quot;">Mehr</a></p>',
            'answer' => '<p><a href="t3://page?uid=1&amp;L=2" title="a &lt; b &amp; &quot;c&quot;" dlt-r="0">More</a></p>',
            'expectedHtml' => '<p><a href="t3://page?uid=1&amp;L=2" title="a &lt; b &amp; &quot;c&quot;">More</a></p>',
        ];
        yield 'control characters and comments' => [
            'html' => "<p>Zeile 1\u{B}Zeile 2<!-- alt -- neu --></p>",
            'answer' => '<p>Line 1 Line 2<!-- alt - - neu --></p>',
            'expectedHtml' => '<p>Line 1 Line 2<!-- alt - - neu --></p>',
        ];
        yield 'DeepL drops a glued element, its space stays' => [
            'html' => '<p>Our<strong>new</strong>offer</p>',
            'answer' => '<p>Unser neues Angebot</p>',
            'expectedHtml' => '<p>Unser neues Angebot</p>',
        ];
        yield 'DeepL drops a placeholder, the sup is lost' => [
            'html' => '<p>FOOBAR<sup>®</sup> now</p>',
            'answer' => '<p>FOOBAR jetzt</p>',
            'expectedHtml' => '<p>FOOBAR jetzt</p>',
        ];
        yield 'a script digit DeepL writes itself becomes an element if the source had a digit sup' => [
            'html' => '<p>m<sup>2</sup> and square metres</p>',
            'answer' => '<p>m² und m²</p>',
            'expectedHtml' => '<p>m<sup>2</sup> und m<sup>2</sup></p>',
        ];
    }

    #[Test]
    #[DataProvider('simulatedDeepLAnswerDataProvider')]
    public function simulatedDeepLAnswerIsReverted(string $html, string $answer, string $expectedHtml): void
    {
        $subject = new HtmlXmlConverter();

        $this->assertWellFormedXml($subject->htmlToXml($html));
        $this->assertSame($expectedHtml, $subject->xmlToHtml($answer, $html)->html);
    }

    public static function translatedSourceAttributesDataProvider(): \Generator
    {
        yield 'attribute names XML does not allow on a block element' => [
            'html' => '<div class="menu" @click="open = !open"><p>Open the menu</p></div>',
            'expectedXml' => '<div class="menu" dlt-a="0"><p>Open the menu</p></div>',
            'answer' => '<div class="menu" dlt-a="0"><p>Menü öffnen</p></div>',
            'expectedHtml' => '<div class="menu" @click="open = !open"><p>Menü öffnen</p></div>',
        ];
        yield 'touching links with event attributes, renamed for DeepL' => [
            'html' => '<p><a href="#a" @click="a()">Call</a><a href="#b" @click="b()">Book</a></p>',
            'expectedXml' => '<p><dlt-s href="#a" dlt-a="1" dlt-r="0">Call</dlt-s><dlt-s href="#b" dlt-a="2" dlt-r="1">Book</dlt-s></p>',
            'answer' => '<p><dlt-s href="#a" dlt-a="1" dlt-r="0">Anrufen</dlt-s><dlt-s href="#b" dlt-a="2" dlt-r="1">Buchen</dlt-s></p>',
            'expectedHtml' => '<p><a href="#a" @click="a()">Anrufen</a><a href="#b" @click="b()">Buchen</a></p>',
        ];
        yield 'number dropped by DeepL, the attributes XML allows are kept' => [
            'html' => '<div class="menu" @click="open = !open"><p>Open the menu</p></div>',
            'expectedXml' => '<div class="menu" dlt-a="0"><p>Open the menu</p></div>',
            'answer' => '<div class="menu"><p>Menü öffnen</p></div>',
            'expectedHtml' => '<div class="menu"><p>Menü öffnen</p></div>',
        ];
        yield 'title and alt are written as in the source' => [
            'html' => '<p><a href="#" title="Read more" data-x=1>Link</a><img src="x.jpg" alt="A cat" ALT="B"></p>',
            'expectedXml' => '<p><a href="#" title="Read more" data-x="1" dlt-a="1" dlt-r="0">Link</a><img src="x.jpg" alt="A cat" dlt-a="2"/></p>',
            'answer' => '<p><a href="#" title="Mehr lesen" data-x="1" dlt-a="1" dlt-r="0">Verweis</a><img src="x.jpg" alt="Eine Katze" dlt-a="2"/></p>',
            'expectedHtml' => '<p><a href="#" title="Read more" data-x=1>Verweis</a><img src="x.jpg" alt="A cat" ALT="B" /></p>',
        ];
    }

    /**
     * Only elements whose attributes the serializer would change carry a number, their attributes are written as in
     * the source, whatever DeepL answers for them.
     */
    #[Test]
    #[DataProvider('translatedSourceAttributesDataProvider')]
    public function translationKeepsTheAttributesOfTheSource(string $html, string $expectedXml, string $answer, string $expectedHtml): void
    {
        $subject = new HtmlXmlConverter();

        $this->assertSame($expectedXml, $subject->htmlToXml($html));
        $this->assertSame($expectedHtml, $subject->xmlToHtml($answer, $html)->html);
    }

    public static function attributeTextsDataProvider(): \Generator
    {
        yield 'no attribute read as text' => [
            'html' => '<p>A <a href="t3://page?uid=1" class="title">link</a> and <span data-alt="x">text</span>.</p>',
            'expectedTexts' => [],
        ];
        yield 'title of a link as reported in issue 427' => [
            'html' => '<p>Test auf <a href="t3://page?uid=1736" title="Deutscher Titel">Deutsch</a></p>',
            'expectedTexts' => ['Deutscher Titel'],
        ];
        yield 'title, alt and aria-label on any element, each value once, in the order of the content' => [
            'html' => '<p title="Paragraph"><abbr title="World Health Organization">WHO</abbr> <img src="x.png" alt="A red bicycle"></p>'
                . '<button type="button" aria-label="Close" title="Close">x</button><a href="#" title="Paragraph">again</a>',
            'expectedTexts' => ['Paragraph', 'World Health Organization', 'A red bicycle', 'Close'],
        ];
        yield 'values as XML text, quotes stay literal' => [
            'html' => '<a href="#" title="Tom &amp; Jerry say &quot;hi&quot; &lt;3">x</a><a href="#" title=\'It&#039;s > 2\'>y</a>',
            'expectedTexts' => ['Tom &amp; Jerry say "hi" &lt;3', 'It\'s &gt; 2'],
        ];
        yield 'characters XML does not allow are replaced like in the content' => [
            'html' => "<a href=\"#\" title=\"First line\u{B}second line\u{1}\">x</a>",
            'expectedTexts' => ['First line second line'],
        ];
        yield 'content marked as not to be translated, the nearest translate attribute decides' => [
            'html' => '<p><a href="#" class="button notranslate" title="Own class">a</a> <span class="notranslate"><a href="#" title="Ancestor class">b</a></span>'
                . ' <a href="#" translate="no" title="Own attribute">c</a> <span translate="NO"><abbr title="Ancestor attribute">d</abbr>'
                . '<span translate="yes"><a href="#" title="Translated again">e</a></span></span></p>',
            'expectedTexts' => ['Translated again'],
        ];
        yield 'an invalid translate value is inherited, the attribute wins over the class of its element' => [
            'html' => '<p translate="no"><a href="#" translate="no " title="Inherited no">a</a></p>'
                . '<p><a href="#" translate="maybe" title="Inherited yes">b</a> <a href="#" class="notranslate" translate="yes" title="Attribute yes">c</a></p>',
            'expectedTexts' => ['Inherited yes', 'Attribute yes'],
        ];
        yield 'line breaks and tabs are sent, equal values apart from them once' => [
            'html' => "<a href=\"#\" title=\"Line one&#10;line\ttwo\">x</a><a href=\"#\" title=\"Line one line two\">y</a>",
            'expectedTexts' => ["Line one\nline\ttwo"],
        ];
        yield 'script and style, empty values and values without a letter' => [
            'html' => '<style title="Print styles">p {}</style><script title="Tracking">x()</script>'
                . '<p><a href="#" title="">a</a> <a href="#" title="  ">b</a> <a href="#" title="2026">c</a> <a href="#" title="→ 50 %">d</a></p>',
            'expectedTexts' => [],
        ];
    }

    /**
     * @param list<string> $expectedTexts
     */
    #[Test]
    #[DataProvider('attributeTextsDataProvider')]
    public function getAttributeTextsReturnsTheValuesReadersSeeAsText(string $html, array $expectedTexts): void
    {
        $subject = new HtmlXmlConverter();

        $attributeTexts = $subject->getAttributeTexts($html);

        $this->assertSame($expectedTexts, $attributeTexts);
        foreach ($attributeTexts as $text) {
            $this->assertWellFormedXml($text);
        }
    }

    public static function translatedAttributeTextsDataProvider(): \Generator
    {
        yield 'title of a link as reported in issue 427' => [
            'html' => '<p>Test auf <a href="t3://page?uid=1736" title="Deutscher Titel">Deutsch</a></p>',
            'answer' => static fn(string $xml): string => str_replace(['Test auf', 'Deutsch<'], ['Test in', 'German<'], $xml),
            'translations' => ['German title'],
            'expectedHtml' => '<p>Test in <a href="t3://page?uid=1736" title="German title">German</a></p>',
        ];
        // Recorded with the real API, German to English: "Anna" moves to the front.
        yield 'links swapped by the word order keep their own title' => [
            'html' => '<p>Den <a href="t3://page?uid=1" title="Der unterschriebene Vertrag">Vertrag</a> hat gestern <a href="t3://page?uid=2" title="Das Profil von Anna">Anna</a> unterschrieben.</p>',
            'answer' => static fn(string $xml): string => '<p><a href="t3://page?uid=2" title="Das Profil von Anna" dlt-r="1">Anna</a> signed the <a href="t3://page?uid=1" title="Der unterschriebene Vertrag" dlt-r="0">contract</a> yesterday.</p>',
            'translations' => ['The signed contract', 'Anna\'s profile'],
            'expectedHtml' => '<p><a href="t3://page?uid=2" title="Anna\'s profile">Anna</a> signed the <a href="t3://page?uid=1" title="The signed contract">contract</a> yesterday.</p>',
        ];
        yield 'both copies of a link DeepL split' => [
            'html' => '<p>Ask our <a href="#advice" title="Contact the advisory service">advisory service</a> today.</p>',
            'answer' => static fn(string $xml): string => '<p>Fragen Sie <a href="#advice" title="Contact the advisory service" dlt-r="0">unsere</a> heute <a href="#advice" title="Contact the advisory service" dlt-r="0">Beratung</a>.</p>',
            'translations' => ['Kontakt zur Beratung'],
            'expectedHtml' => '<p>Fragen Sie <a href="#advice" title="Kontakt zur Beratung">unsere</a> heute <a href="#advice" title="Kontakt zur Beratung">Beratung</a>.</p>',
        ];
        yield 'touching links sent under the helper name, the revert restores their title' => [
            'html' => '<p><a href="#a" title="Call us now">Call</a><a href="#b" title="Book a date">Book</a></p>',
            'answer' => static fn(string $xml): string => str_replace(['>Call<', '>Book<'], ['>Anrufen<', '>Buchen<'], $xml),
            'translations' => ['Jetzt anrufen', 'Termin buchen'],
            'expectedHtml' => '<p><a href="#a" title="Jetzt anrufen">Anrufen</a><a href="#b" title="Termin buchen">Buchen</a></p>',
        ];
        yield 'image, abbreviation and button' => [
            'html' => '<p><abbr title="World Health Organization">WHO</abbr> <img src="x.png" alt="A red bicycle" /></p><button type="button" aria-label="Close the dialog">x</button>',
            'answer' => static fn(string $xml): string => $xml,
            'translations' => ['Weltgesundheitsorganisation', 'Ein rotes Fahrrad', 'Dialog schließen'],
            'expectedHtml' => '<p><abbr title="Weltgesundheitsorganisation">WHO</abbr> <img src="x.png" alt="Ein rotes Fahrrad" /></p><button type="button" aria-label="Dialog schließen">x</button>',
        ];
        yield 'answer in XML text, written escaped as rich text stores it' => [
            'html' => '<a href="#" title="Tom &amp; Jerry say &quot;hi&quot; &lt;3">x</a>',
            'answer' => static fn(string $xml): string => $xml,
            'translations' => ['Tom &amp; Jerry sagen „hallo“ "Hi" &lt;3'],
            'expectedHtml' => '<a href="#" title="Tom &amp; Jerry sagen „hallo“ &quot;Hi&quot; &lt;3">x</a>',
        ];
        yield 'the same value marked as not to be translated stays' => [
            'html' => '<p><a href="#a" title="Read more">a</a> <a href="#b" class="notranslate" title="Read more">b</a></p>',
            'answer' => static fn(string $xml): string => $xml,
            'translations' => ['Mehr lesen'],
            'expectedHtml' => '<p><a href="#a" title="Mehr lesen">a</a> <a href="#b" class="notranslate" title="Read more">b</a></p>',
        ];
        yield 'an empty translation keeps the value of the source' => [
            'html' => '<p><a href="#a" title="Read more">a</a></p>',
            'answer' => static fn(string $xml): string => $xml,
            'translations' => [' '],
            'expectedHtml' => '<p><a href="#a" title="Read more">a</a></p>',
        ];
        yield 'value with characters XML does not allow' => [
            'html' => "<p><a href=\"#a\" title=\"First line\u{B}second line\">a</a></p>",
            'answer' => static fn(string $xml): string => $xml,
            'translations' => ['Erste Zeile zweite Zeile'],
            'expectedHtml' => '<p><a href="#a" title="Erste Zeile zweite Zeile">a</a></p>',
        ];
        yield 'a value DeepL returns unchanged is kept as stored, also in the attribute text of the source' => [
            'html' => '<p><a href="#a" title="Tom & Jerry" @click="a()">a</a> <a href="#b" title=Anna data-x=1>b</a> <a href="#c" title="Caf&eacute; Anna">c</a></p>',
            'answer' => static fn(string $xml): string => $xml,
            'translations' => ['Tom &amp; Jerry', 'Anna', 'Café Anna'],
            'expectedHtml' => '<p><a href="#a" title="Tom & Jerry" @click="a()">a</a> <a href="#b" title=Anna data-x=1>b</a> <a href="#c" title="Caf&eacute; Anna">c</a></p>',
        ];
        yield 'spaces around a value are kept' => [
            'html' => '<p><a href="#a" title=" Read more ">a</a></p>',
            'answer' => static fn(string $xml): string => $xml,
            'translations' => ['Mehr lesen'],
            'expectedHtml' => '<p><a href="#a" title=" Mehr lesen ">a</a></p>',
        ];
        yield 'non-breaking spaces around a value are kept once' => [
            'html' => "<p><a href=\"#a\" title=\"\u{A0}Read more\">a</a> <a href=\"#b\" title=\"Price\u{202F}\">b</a> <a href=\"#c\" title=\"Name\u{A0}\">c</a></p>",
            'answer' => static fn(string $xml): string => $xml,
            'translations' => ["\u{A0}Mehr lesen", "Preis\u{202F}", "Name\u{A0}"],
            // Written as the attribute text of the source, which has the characters, not references.
            'expectedHtml' => "<p><a href=\"#a\" title=\"\u{A0}Mehr lesen\">a</a> <a href=\"#b\" title=\"Preis\u{202F}\">b</a> <a href=\"#c\" title=\"Name\u{A0}\">c</a></p>",
        ];
        yield 'a value with a line break DeepL returns unchanged is kept as stored' => [
            'html' => '<p><a href="#a" title="Line one&#10;line two">a</a> <a href="#b" title="Read more&#10;">b</a></p>',
            'answer' => static fn(string $xml): string => $xml,
            'translations' => ["Line one\nline two", 'Mehr lesen'],
            // The tags are written as the attribute text of the source, the translated line break as character.
            'expectedHtml' => "<p><a href=\"#a\" title=\"Line one&#10;line two\">a</a> <a href=\"#b\" title=\"Mehr lesen\n\">b</a></p>",
        ];
        yield 'a line break DeepL answers as character, which XML reads as a space' => [
            'html' => '<p><a href="#a" title="Line one&#10;line two">a</a></p>',
            'answer' => static fn(string $xml): string => str_replace('&#10;', "\n", $xml),
            'translations' => ['Zeile eins Zeile zwei'],
            'expectedHtml' => '<p><a href="#a" title="Zeile eins Zeile zwei">a</a></p>',
        ];
        yield 'a translation equal to another value of the source is not translated again' => [
            'html' => '<p><a href="#a" title="Hund">a</a> <a href="#b" title="Dog">b</a></p>',
            'answer' => static fn(string $xml): string => $xml,
            'translations' => ['Dog', 'Doggy'],
            'expectedHtml' => '<p><a href="#a" title="Dog">a</a> <a href="#b" title="Doggy">b</a></p>',
        ];
        yield 'content using a helper name, sent without preparation' => [
            'html' => '<p dlt-x="1"><a href="#a" title="Read more">a</a></p>',
            'answer' => static fn(string $xml): string => $xml,
            'translations' => ['Mehr lesen'],
            'expectedHtml' => '<p dlt-x="1"><a href="#a" title="Mehr lesen">a</a></p>',
        ];
        yield 'a link in a sup sent as placeholder' => [
            'html' => '<p>See the note<sup><a href="#fn1" title="Read the footnote">1</a></sup>.</p>',
            'answer' => static fn(string $xml): string => $xml,
            'translations' => ['Fußnote lesen'],
            'expectedHtml' => '<p>See the note<sup><a href="#fn1" title="Fußnote lesen">1</a></sup>.</p>',
        ];
        yield 'both copies of an element written as the attribute text of the source' => [
            'html' => '<p>Ask our <a href="#advice" @click="track()" title="Contact the advisory service">advisory service</a> today.</p>',
            'answer' => static fn(string $xml): string => (string)preg_replace('#<a ([^>]*)>advisory service</a>#', '<a $1>advisory</a> and <a $1>service</a>', $xml),
            'translations' => ['Kontakt zur Beratung'],
            'expectedHtml' => '<p>Ask our <a href="#advice" @click="track()" title="Kontakt zur Beratung">advisory</a> and <a href="#advice" @click="track()" title="Kontakt zur Beratung">service</a> today.</p>',
        ];
        yield 'attributes written as in the source get the translation in the attribute text' => [
            'html' => '<p><a href="#" title="Read more" data-x=1>Link</a><img src="x.jpg" alt="A cat" ALT="B"></p>'
                . '<button type="button" title=\'Show the "opening" hours\' @click="open = !open" aria-label=Close>Opening hours</button>',
            'answer' => static fn(string $xml): string => $xml,
            'translations' => ['Mehr lesen', 'Eine Katze', 'Die „Öffnungs“-Zeiten & \'mehr\' > zeigen', 'Schließen'],
            'expectedHtml' => '<p><a href="#" title="Mehr lesen" data-x=1>Link</a><img src="x.jpg" alt="Eine Katze" ALT="B" /></p>'
                . '<button type="button" title=\'Die „Öffnungs“-Zeiten &amp; &#039;mehr&#039; &gt; zeigen\' @click="open = !open" aria-label="Schließen">Opening hours</button>',
        ];
    }

    /**
     * @param \Closure(string): string $answer DeepL's answer for the XML of the content
     * @param list<string> $translations DeepL's answer for the attribute texts
     */
    #[Test]
    #[DataProvider('translatedAttributeTextsDataProvider')]
    public function xmlToHtmlReplacesAttributeValuesByTheirTranslation(string $html, \Closure $answer, array $translations, string $expectedHtml): void
    {
        $subject = new HtmlXmlConverter();
        $this->assertCount(count($translations), $subject->getAttributeTexts($html));

        $this->assertSame($expectedHtml, $subject->xmlToHtml($answer($subject->htmlToXml($html)), $html, $translations)->html);
    }

    #[Test]
    public function xmlToHtmlThrowsExceptionIfTheAttributeTranslationsDoNotBelongToTheSource(): void
    {
        $subject = new HtmlXmlConverter();
        $html = '<p><a href="#a" title="Read more">a</a></p>';

        $this->expectException(XmlConversionException::class);
        $this->expectExceptionCode(1790969971);

        $subject->xmlToHtml($subject->htmlToXml($html), $html, ['Mehr lesen', 'Zu viel']);
    }

    public static function xmlToHtmlDataProvider(): \Generator
    {
        yield 'translated issue 642 result' => [
            'xml' => "<p>\n    Dirección postal:<br/>\n    Apartado de correos 1234<br/>\n</p>",
            'expectedHtml' => "<p>\n    Dirección postal:<br />\n    Apartado de correos 1234<br />\n</p>",
        ];
        yield 'self-closed non-void element gets an end tag' => [
            'xml' => '<p/><span class="x"/>',
            'expectedHtml' => '<p></p><span class="x"></span>',
        ];
        yield 'character references' => [
            'xml' => '<p>A&#160;B &#x263A; &amp; &lt;</p>',
            'expectedHtml' => "<p>A&nbsp;B \u{263A} &amp; &lt;</p>",
        ];
        yield 'text and elements on top level' => [
            'xml' => 'Text <strong>fett</strong> Ende',
            'expectedHtml' => 'Text <strong>fett</strong> Ende',
        ];
    }

    #[Test]
    #[DataProvider('xmlToHtmlDataProvider')]
    public function xmlToHtmlReturnsHtml(string $xml, string $expectedHtml): void
    {
        $this->assertSame($expectedHtml, (new HtmlXmlConverter())->xmlToHtml($xml, '')->html);
    }

    #[Test]
    public function xmlToHtmlThrowsExceptionOnMalformedXml(): void
    {
        $this->expectException(XmlConversionException::class);
        $this->expectExceptionCode(1789723672);
        $this->expectExceptionMessage('Opening and ending tag mismatch');

        (new HtmlXmlConverter())->xmlToHtml('<p>Nicht geschlossen<br></p>', '<p>Nicht geschlossen<br></p>');
    }

    /**
     * The preparation must not change what it does not translate: with DeepL returning the prepared XML
     * unchanged, the result equals the conversion without any preparation. Random nested inline markup, sup and
     * sub, attributes, entities and line feeds, seeded so a failure is reproducible.
     */
    public static function randomContentDataProvider(): \Generator
    {
        foreach ([1, 2, 3, 4] as $seed) {
            yield 'seed ' . $seed => ['seed' => $seed];
        }
    }

    #[Test]
    #[DataProvider('randomContentDataProvider')]
    public function roundTripOfRandomContentEqualsTheConversionWithoutPreparation(int $seed): void
    {
        $randomizer = new Randomizer(new Mt19937($seed));
        $subject = new HtmlXmlConverter();
        for ($iteration = 0; $iteration < 500; $iteration++) {
            $html = '<p>' . $this->createRandomInlineContent($randomizer, 0) . '</p>'
                . ($randomizer->getInt(0, 1) === 1 ? '<pre>' . $this->createRandomInlineContent($randomizer, 0) . '</pre>' : '');
            $xml = $subject->htmlToXml($html);
            $this->assertWellFormedXml($xml);
            // Adjacent digit elements come back as one element if the content has no script digit, see ScriptDigitStep.
            $expected = $this->convertWithoutPreparation($html);
            do {
                if (preg_match('/[⁰¹²³⁴⁵⁶⁷⁸⁹₀₁₂₃₄₅₆₇₈₉]/u', $html) === 1) {
                    break;
                }
                $previous = $expected;
                $expected = (string)preg_replace('#<(sup|sub)>([0-9]+)</\1><\1>([0-9]+)</\1>#', '<$1>$2$3</$1>', $expected);
            } while ($expected !== $previous);
            $this->assertSame($expected, $subject->xmlToHtml($xml, $html)->html, 'Input: ' . $html);
        }
    }

    /**
     * Every inline element carries its reference number, tags are not billed but count for the request size
     * limit of 128 KiB (https://developers.deepl.com/docs/resources/usage-limits).
     */
    public static function requestSizeDataProvider(): \Generator
    {
        yield 'paragraph with a few inline elements' => [
            'html' => '<p>We process your <strong>personal data</strong> according to <a href="t3://page?uid=5">Art. 6 GDPR</a>. Details are in <em>section 3</em> of this <a href="https://example.org/privacy">privacy policy</a>.</p>',
            'maximumRatio' => 1.2,
        ];
        yield 'paragraph dense with glued words, touching links and sup' => [
            'html' => '<p>Text with <strong>bold</strong>, e<strong>Mail</strong>, m<sup>2</sup>, FOOBAR<sup>®</sup>, <a href="t3://page?uid=1">link</a><a href="t3://page?uid=2">next</a> and <em>more <i>nested</i> text</em>.</p>',
            'maximumRatio' => 1.6,
        ];
    }

    #[Test]
    #[DataProvider('requestSizeDataProvider')]
    public function preparationKeepsTheRequestSmall(string $html, float $maximumRatio): void
    {
        $xml = (new HtmlXmlConverter())->htmlToXml($html);

        $this->assertLessThanOrEqual($maximumRatio * strlen($this->convertWithoutPreparation($html)), strlen($xml), $xml);
    }

    /**
     * The HTML parser lowercases element and attribute names, a helper name in another case is a helper name too.
     */
    public static function contentSentWithoutPreparationDataProvider(): \Generator
    {
        yield 'helper attribute' => ['<p><a href="1">Call</a><a href="2" dlt-r="0">Book</a></p>'];
        yield 'helper attribute in upper case' => ['<p><span DLT-R="1">a</span> <i>b</i></p>'];
        yield 'helper element in mixed case' => ['<p>a<Dlt-P></Dlt-P>b <em>c</em>d</p>'];
    }

    #[Test]
    #[DataProvider('contentSentWithoutPreparationDataProvider')]
    public function tagHandlingOptionsAreEmptyForContentSentWithoutPreparation(string $html): void
    {
        $subject = new HtmlXmlConverter();

        $this->assertSame(['splitting_tags' => [], 'non_splitting_tags' => []], $subject->getTagHandlingOptions($html));
        // The conversion decides the same way, it adds no reference number.
        $this->assertSame(
            substr_count(strtolower($html), 'dlt-r='),
            substr_count($subject->htmlToXml($html), 'dlt-r=')
        );
    }

    #[Test]
    public function xmlToHtmlThrowsExceptionIfTheSourceIsNotTheSentContent(): void
    {
        $subject = new HtmlXmlConverter();
        $xml = $subject->htmlToXml('<p><a href="1">Call</a><a href="2">Book</a> and FOO<sup>®</sup></p>');

        $this->expectException(XmlConversionException::class);
        $this->expectExceptionCode(1790943110);

        $subject->xmlToHtml($xml, '<p><strong>New</strong> <a href="1">Call</a><a href="2">Book</a> and FOO<sup>®</sup></p>');
    }

    #[Test]
    public function xmlToHtmlReportsALinkMissingInTheTranslation(): void
    {
        $html = '<p>Das ist die Garantie<em>verlängerung</em> für Ihr Fahr<a href="t3://page?uid=5">rad</a> und <a href="#x">mehr</a>.</p>';

        $result = (new HtmlXmlConverter())->xmlToHtml(
            '<p>This is the <em dlt-r="0">extended</em> warranty for your bicycle and <a href="#x" dlt-r="2">more</a>.</p>',
            $html
        );

        $this->assertSame('<p>This is the <em>extended</em> warranty for your bicycle and <a href="#x">more</a>.</p>', $result->html);
        $this->assertEquals([new LostLink('t3://page?uid=5', 'rad')], $result->lostLinks);
    }

    /**
     * The editor gets the link text as stored, not as prepared for DeepL.
     */
    public static function lostLinkTextDataProvider(): \Generator
    {
        yield 'glued element in the link' => ['<p>Mein <a href="t3://page?uid=5">Fahr<em>rad</em></a> ist neu.</p>', 'Fahrrad'];
        yield 'symbol in sup' => ['<p>Kauf <a href="t3://page?uid=5">FOOBAR<sup>®</sup></a> heute.</p>', 'FOOBAR®'];
        yield 'digit in sup' => ['<p>Die <a href="t3://page?uid=5">Fläche in m<sup>2</sup></a> zählt.</p>', 'Fläche in m2'];
        yield 'touching links' => ['<p><a href="1">Call</a><a href="t3://page?uid=5">Book<sup>2</sup></a></p>', 'Book2'];
    }

    #[Test]
    #[DataProvider('lostLinkTextDataProvider')]
    public function xmlToHtmlReportsTheLostLinkWithItsTextAsStored(string $html, string $expectedText): void
    {
        $subject = new HtmlXmlConverter();
        $xml = $subject->htmlToXml($html);
        // DeepL drops the link to page 5 and keeps its text.
        $answer = (string)preg_replace('#<(a|dlt-s) href="t3://page\?uid=5"[^>]*>(.*?)</\1>#', '$2', $xml);
        $this->assertNotSame($xml, $answer);

        $result = $subject->xmlToHtml($answer, $html);

        $this->assertEquals([new LostLink('t3://page?uid=5', $expectedText)], $result->lostLinks);
    }

    #[Test]
    public function xmlToHtmlReportsNoLinkIfEveryLinkIsThere(): void
    {
        $subject = new HtmlXmlConverter();
        $html = '<p><a href="1">Call</a><a href="2">Book</a>, <a href="3">more</a> and <a href="4">a</a> <a href="4">b</a></p>';

        $this->assertSame([], $subject->xmlToHtml($subject->htmlToXml($html), $html)->lostLinks);
    }

    #[Test]
    public function tagHandlingOptionsNameTheHelperElements(): void
    {
        $options = (new HtmlXmlConverter())->getTagHandlingOptions('<p><a href="1">Call</a><a href="2">Book</a></p>');

        $this->assertSame(['dlt-s'], $options['splitting_tags']);
        $this->assertContains('a', $options['non_splitting_tags']);
        $this->assertContains('dlt-p', $options['non_splitting_tags']);
        $this->assertNotContains('dlt-k', $options['non_splitting_tags']);
    }

    private function createRandomInlineContent(Randomizer $randomizer, int $depth): string
    {
        $texts = ['a', 'Wort', ' ', '  ', 'e', 'i', '1', '2021', '.', ',', '-', '„', '“', '®', '€', '&amp;', '&lt;', '&gt;', '&nbsp;', "\n", "\r\n", 'x y', ' z ', '²', '漢', 'é', '(', ')', '*', '&quot;'];
        $inline = ['a', 'b', 'em', 'strong', 'i', 'span', 'sup', 'sub', 'u', 'code', 'small', 'mark', 'abbr'];
        $attributes = ['', '', '', ' href="t3://page?uid=1&amp;x=2"', ' class="c"', ' title="a &lt; b &quot;q&quot;"', ' xml:lang="de"', ' id="n"'];
        $pick = static fn(array $values): string => $values[$randomizer->getInt(0, count($values) - 1)];
        $content = '';
        for ($count = $randomizer->getInt(0, 4); $count > 0; $count--) {
            $kind = $randomizer->getInt(0, 9);
            if ($kind < 4 || $depth > 3) {
                $content .= $pick($texts);
            } elseif ($kind < 5) {
                $content .= $pick(['<br>', '<br/>', '<!-- c -->', '<img src="x" alt="y">', '<wbr>']);
            } elseif ($kind < 7) {
                $name = $pick(['sup', 'sub']);
                $content .= '<' . $name . ($randomizer->getInt(0, 4) === 0 ? $pick($attributes) : '') . '>'
                    . $pick([(string)$randomizer->getInt(0, 9999), '®', '*)', 'st', '†', (string)$randomizer->getInt(0, 9)])
                    . '</' . $name . '>';
            } else {
                $name = $pick($inline);
                $content .= '<' . $name . $pick($attributes) . '>' . $this->createRandomInlineContent($randomizer, $depth + 1) . '</' . $name . '>';
            }
        }
        return $content;
    }

    /**
     * The conversion as it was before the preparation steps existed, with the serializer of the converter.
     */
    private function convertWithoutPreparation(string $html): string
    {
        $html5 = new RichTextHtml5(['disable_html_ns' => true]);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $xml = '';
        foreach ($html5->loadHTMLFragment((string)preg_replace('#<(?![a-zA-Z!/?])#', '&lt;', $html))->childNodes as $node) {
            $xml .= $document->saveXML($document->importNode($node, true));
        }
        $xml = (string)preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $xml);
        $translation = new \DOMDocument();
        $this->assertTrue($translation->loadXML('<root>' . $xml . '</root>'), $xml);
        $result = '';
        $this->assertInstanceOf(\DOMElement::class, $translation->documentElement);
        foreach ($translation->documentElement->childNodes as $node) {
            $result .= $html5->saveHTML($node);
        }
        return $result;
    }

    private function assertWellFormedXml(string $xml): void
    {
        $useInternalErrors = libxml_use_internal_errors(true);
        $loaded = (new \DOMDocument())->loadXML('<root>' . $xml . '</root>');
        $errors = array_map(static fn(\LibXMLError $error): string => trim($error->message), libxml_get_errors());
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);
        $this->assertTrue($loaded, $xml . "\n" . implode("\n", $errors));
    }
}
