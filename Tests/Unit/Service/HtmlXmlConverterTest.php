<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Service;

use Masterminds\HTML5;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WebVision\Deepltranslate\Core\Exception\XmlConversionException;
use WebVision\Deepltranslate\Core\Service\HtmlXmlConverter;
use WebVision\Deepltranslate\Core\Service\LostLink;

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

        static::assertSame($expectedXml, $xml);
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
        yield 'attribute values with less-than, greater-than, ampersand and quotes keep their value, masterminds/html5 writes < and > unescaped' => [
            'html' => '<p><a href="t3://page?uid=1&amp;L=2" title="a &lt; b &gt; c &amp; &quot;d&quot; \'e\'">x</a></p>',
            'expectedHtml' => '<p><a href="t3://page?uid=1&amp;L=2" title="a < b > c &amp; &quot;d&quot; \'e\'">x</a></p>',
        ];
        yield 'void elements' => [
            'html' => '<p>Zeile 1<br>Zeile 2</p><hr><p><img src="t3://file?uid=5" alt="Ein Bild" width="300" height="200"></p>',
            'expectedHtml' => '<p>Zeile 1<br>Zeile 2</p><hr><p><img src="t3://file?uid=5" alt="Ein Bild" width="300" height="200"></p>',
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
            'expectedHtml' => self::ISSUE_642_HTML,
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
        yield 'helper elements and attributes in the content, masterminds/html5 writes < and > in attributes unescaped' => [
            'html' => '<p>a<dlt-p dlt-r="0" markup="&lt;b&gt;x&lt;/b&gt;"></dlt-p>b</p><p><span dlt-r="5">a</span><span dlt-g="x">b</span> c</p><div dlt-r="0"></div><p><em>x</em></p>',
            'expectedHtml' => '<p>a<dlt-p dlt-r="0" markup="<b>x</b>"></dlt-p>b</p><p><span dlt-r="5">a</span><span dlt-g="x">b</span> c</p><div dlt-r="0"></div><p><em>x</em></p>',
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
        static::assertSame($expectedHtml, $subject->xmlToHtml($xml, $html)->html);
    }

    /**
     * The answers DeepL gave on 2026-10-01, with the options of {@see \WebVision\Deepltranslate\Core\Client}.
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
        static::assertSame($expectedHtml, $subject->xmlToHtml($answer, $html)->html);
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
            'expectedHtml' => '<p><a href="t3://page?uid=1&amp;L=2" title="a < b &amp; &quot;c&quot;">More</a></p>',
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
        static::assertSame($expectedHtml, $subject->xmlToHtml($answer, $html)->html);
    }

    public static function xmlToHtmlDataProvider(): \Generator
    {
        yield 'translated issue 642 result' => [
            'xml' => "<p>\n    Dirección postal:<br/>\n    Apartado de correos 1234<br/>\n</p>",
            'expectedHtml' => "<p>\n    Dirección postal:<br>\n    Apartado de correos 1234<br>\n</p>",
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
        static::assertSame($expectedHtml, (new HtmlXmlConverter())->xmlToHtml($xml, '')->html);
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
        // PHP 8.1 has no Random\Randomizer. mt_rand() seeded by mt_srand() returns the same numbers as the
        // Randomizer with the Mt19937 engine, so the inputs are the same on every supported PHP version.
        mt_srand($seed);
        try {
            $subject = new HtmlXmlConverter();
            for ($iteration = 0; $iteration < 500; $iteration++) {
                $html = '<p>' . $this->createRandomInlineContent(0) . '</p>'
                    . (mt_rand(0, 1) === 1 ? '<pre>' . $this->createRandomInlineContent(0) . '</pre>' : '');
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
                static::assertSame($expected, $subject->xmlToHtml($xml, $html)->html, 'Input: ' . $html);
            }
        } finally {
            // Other tests get unseeded numbers again.
            mt_srand();
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

        static::assertLessThanOrEqual($maximumRatio * strlen($this->convertWithoutPreparation($html)), strlen($xml), $xml);
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

        static::assertSame(['splitting_tags' => [], 'non_splitting_tags' => []], $subject->getTagHandlingOptions($html));
        // The conversion decides the same way, it adds no reference number.
        static::assertSame(
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

        static::assertSame('<p>This is the <em>extended</em> warranty for your bicycle and <a href="#x">more</a>.</p>', $result->html);
        static::assertEquals([new LostLink('t3://page?uid=5', 'rad')], $result->lostLinks);
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
        static::assertNotSame($xml, $answer);

        $result = $subject->xmlToHtml($answer, $html);

        static::assertEquals([new LostLink('t3://page?uid=5', $expectedText)], $result->lostLinks);
    }

    #[Test]
    public function xmlToHtmlReportsNoLinkIfEveryLinkIsThere(): void
    {
        $subject = new HtmlXmlConverter();
        $html = '<p><a href="1">Call</a><a href="2">Book</a>, <a href="3">more</a> and <a href="4">a</a> <a href="4">b</a></p>';

        static::assertSame([], $subject->xmlToHtml($subject->htmlToXml($html), $html)->lostLinks);
    }

    #[Test]
    public function tagHandlingOptionsNameTheHelperElements(): void
    {
        $options = (new HtmlXmlConverter())->getTagHandlingOptions('<p><a href="1">Call</a><a href="2">Book</a></p>');

        static::assertSame(['dlt-s'], $options['splitting_tags']);
        static::assertContains('a', $options['non_splitting_tags']);
        static::assertContains('dlt-p', $options['non_splitting_tags']);
        static::assertNotContains('dlt-k', $options['non_splitting_tags']);
    }

    private function createRandomInlineContent(int $depth): string
    {
        $texts = ['a', 'Wort', ' ', '  ', 'e', 'i', '1', '2021', '.', ',', '-', '„', '“', '®', '€', '&amp;', '&lt;', '&gt;', '&nbsp;', "\n", "\r\n", 'x y', ' z ', '²', '漢', 'é', '(', ')', '*', '&quot;'];
        $inline = ['a', 'b', 'em', 'strong', 'i', 'span', 'sup', 'sub', 'u', 'code', 'small', 'mark', 'abbr'];
        $attributes = ['', '', '', ' href="t3://page?uid=1&amp;x=2"', ' class="c"', ' title="a &lt; b &quot;q&quot;"', ' xml:lang="de"', ' id="n"'];
        $pick = static fn (array $values): string => $values[mt_rand(0, count($values) - 1)];
        $content = '';
        for ($count = mt_rand(0, 4); $count > 0; $count--) {
            $kind = mt_rand(0, 9);
            if ($kind < 4 || $depth > 3) {
                $content .= $pick($texts);
            } elseif ($kind < 5) {
                $content .= $pick(['<br>', '<br/>', '<!-- c -->', '<img src="x" alt="y">', '<wbr>']);
            } elseif ($kind < 7) {
                $name = $pick(['sup', 'sub']);
                $content .= '<' . $name . (mt_rand(0, 4) === 0 ? $pick($attributes) : '') . '>'
                    . $pick([(string)mt_rand(0, 9999), '®', '*)', 'st', '†', (string)mt_rand(0, 9)])
                    . '</' . $name . '>';
            } else {
                $name = $pick($inline);
                $content .= '<' . $name . $pick($attributes) . '>' . $this->createRandomInlineContent($depth + 1) . '</' . $name . '>';
            }
        }
        return $content;
    }

    /**
     * The conversion as it was before the preparation steps existed.
     */
    private function convertWithoutPreparation(string $html): string
    {
        $html5 = new HTML5(['disable_html_ns' => true]);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $xml = '';
        foreach ($html5->loadHTMLFragment((string)preg_replace('#<(?![a-zA-Z!/?])#', '&lt;', $html))->childNodes as $node) {
            $xml .= $document->saveXML($document->importNode($node, true));
        }
        $xml = (string)preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $xml);
        $translation = new \DOMDocument();
        static::assertTrue($translation->loadXML('<root>' . $xml . '</root>'), $xml);
        $result = '';
        static::assertInstanceOf(\DOMElement::class, $translation->documentElement);
        foreach ($translation->documentElement->childNodes as $node) {
            $result .= $html5->saveHTML($node);
        }
        return $result;
    }

    private function assertWellFormedXml(string $xml): void
    {
        $useInternalErrors = libxml_use_internal_errors(true);
        $loaded = (new \DOMDocument())->loadXML('<root>' . $xml . '</root>');
        $errors = array_map(static fn (\LibXMLError $error): string => trim($error->message), libxml_get_errors());
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);
        static::assertTrue($loaded, $xml . "\n" . implode("\n", $errors));
    }
}
