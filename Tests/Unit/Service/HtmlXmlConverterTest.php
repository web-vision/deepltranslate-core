<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WebVision\Deepltranslate\Core\Exception\XmlConversionException;
use WebVision\Deepltranslate\Core\Service\HtmlXmlConverter;

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
            'expectedXml' => "<p>Preis:\u{00A0}100\u{00A0}€</p>",
        ];
        yield 'issue 642 markup' => [
            'html' => self::ISSUE_642_HTML,
            'expectedXml' => str_replace('<br>', '<br/>', self::ISSUE_642_HTML),
        ];
        yield 'issue 665 markup' => [
            'html' => self::ISSUE_665_HTML,
            'expectedXml' => self::ISSUE_665_HTML,
        ];
    }

    #[Test]
    #[DataProvider('htmlToXmlDataProvider')]
    public function htmlToXmlReturnsWellFormedXml(string $html, string $expectedXml): void
    {
        $xml = (new HtmlXmlConverter())->htmlToXml($html);

        static::assertSame($expectedXml, $xml);
        $document = new \DOMDocument();
        static::assertTrue($document->loadXML('<root>' . $xml . '</root>'));
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
        yield 'void elements' => [
            'html' => '<p>Zeile 1<br>Zeile 2</p><hr><p><img src="t3://file?uid=5" alt="Ein Bild" width="300" height="200"></p>',
            'expectedHtml' => '<p>Zeile 1<br>Zeile 2</p><hr><p><img src="t3://file?uid=5" alt="Ein Bild" width="300" height="200"></p>',
        ];
        yield 'comments' => [
            'html' => '<!-- Hinweis für Redakteure --><p>Text</p>',
            'expectedHtml' => '<!-- Hinweis für Redakteure --><p>Text</p>',
        ];
        yield 'nested inline markup' => [
            'html' => '<p>Wir bieten <strong>schnelle <em>und <a href="t3://page?uid=3">kostenlose</a></em></strong> Lieferung.</p>',
            'expectedHtml' => '<p>Wir bieten <strong>schnelle <em>und <a href="t3://page?uid=3">kostenlose</a></em></strong> Lieferung.</p>',
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
            'html' => '<p></p><p>&nbsp;</p>',
            'expectedHtml' => '<p></p><p>&nbsp;</p>',
        ];
        yield 'issue 642 markup' => [
            'html' => self::ISSUE_642_HTML,
            'expectedHtml' => self::ISSUE_642_HTML,
        ];
        yield 'issue 665 markup' => [
            'html' => self::ISSUE_665_HTML,
            'expectedHtml' => self::ISSUE_665_HTML,
        ];
    }

    #[Test]
    #[DataProvider('roundTripDataProvider')]
    public function roundTripKeepsContentUntouchedByTranslation(string $html, string $expectedHtml): void
    {
        $subject = new HtmlXmlConverter();

        static::assertSame($expectedHtml, $subject->xmlToHtml($subject->htmlToXml($html)));
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
        static::assertSame($expectedHtml, (new HtmlXmlConverter())->xmlToHtml($xml));
    }

    #[Test]
    public function xmlToHtmlThrowsExceptionOnMalformedXml(): void
    {
        $this->expectException(XmlConversionException::class);
        $this->expectExceptionCode(1789723672);

        (new HtmlXmlConverter())->xmlToHtml('<p>Nicht geschlossen<br></p>');
    }
}
