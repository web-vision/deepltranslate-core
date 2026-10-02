<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Service\XmlPreparation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\GluedWordStep;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\InlineMarkup;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\XmlPreparationStepInterface;

#[CoversClass(GluedWordStep::class)]
#[CoversClass(InlineMarkup::class)]
final class GluedWordStepTest extends AbstractXmlPreparationStepTestCase
{
    protected function createSubject(): XmlPreparationStepInterface
    {
        return new GluedWordStep();
    }

    public static function prepareDataProvider(): \Generator
    {
        yield 'issue 311, words glued to elements on both sides' => [
            'xml' => '<p>Our<strong>new</strong>offer, the free<a href="t3://page?uid=40">delivery</a>and</p>',
            'expectedXml' => '<p>Our <strong dlt-r="0">new</strong> offer, the free <a href="t3://page?uid=40" dlt-r="1">delivery</a> and</p>',
        ];
        yield 'a single styled letter is glued like any word' => [
            'xml' => '<p>like e<strong>Mail</strong> and i<em>Phone</em></p>',
            'expectedXml' => '<p>like e <strong dlt-r="0">Mail</strong> and i <em dlt-r="1">Phone</em></p>',
        ];
        yield 'compounds of a German source' => [
            'xml' => '<p>Unsere Produkt<strong>neuheiten</strong>, die <strong>Versand</strong>kosten</p>',
            'expectedXml' => '<p>Unsere Produkt <strong dlt-r="0">neuheiten</strong>, die <strong dlt-r="1">Versand</strong> kosten</p>',
        ];
        yield 'symbols are word characters' => [
            'xml' => '<p>100<strong>€</strong></p>',
            'expectedXml' => '<p>100 <strong dlt-r="0">€</strong></p>',
        ];
        yield 'nested elements' => [
            'xml' => '<p>Our<strong>new<em>big</em></strong>offer</p>',
            'expectedXml' => '<p>Our <strong dlt-r="0">new <em dlt-r="1">big</em></strong> offer</p>',
        ];
        yield 'issue 278, spaces, punctuation and non-breaking spaces are no glue' => [
            'xml' => "<p>thrips (<em>Frankliniella</em>) and wasps<em>\u{A0}Diglyphus</em>\u{A0}<em>isaea</em>, <b>x</b>.</p>",
            'expectedXml' => "<p>thrips (<em dlt-r=\"0\">Frankliniella</em>) and wasps<em dlt-r=\"1\">\u{A0}Diglyphus</em>\u{A0}<em dlt-r=\"2\">isaea</em>, <b dlt-r=\"3\">x</b>.</p>",
        ];
        yield 'a line feed before or after an element is no glue' => [
            'xml' => "<p>See the following\n<a href=\"#\">link</a>\nnow</p><pre><code>const a = 1\n<span class=\"k\">return</span> a\n</code></pre>",
            'expectedXml' => "<p>See the following\n<a href=\"#\" dlt-r=\"0\">link</a>\nnow</p><pre><code dlt-r=\"1\">const a = 1\n<span class=\"k\" dlt-r=\"2\">return</span> a\n</code></pre>",
        ];
        yield 'touching inline elements and other elements are left alone' => [
            'xml' => '<p><a href="#">Call</a><a href="#">Book</a> line<br/>next X<dlt-p/> a<img src="x"/>b</p>',
            'expectedXml' => '<p><a href="#" dlt-r="0">Call</a><a href="#" dlt-r="1">Book</a> line<br/>next X<dlt-p/> a<img src="x"/>b</p>',
        ];
    }

    #[Test]
    #[DataProvider('prepareDataProvider')]
    public function prepareSeparatesGluedWords(string $xml, string $expectedXml): void
    {
        static::assertSame($expectedXml, $this->prepare($xml));
    }

    public static function revertDataProvider(): \Generator
    {
        yield 'the source words are kept, the glue comes back' => [
            'xml' => '<p>Our <strong dlt-r="0">new</strong> offer, FOO <b dlt-r="1">BAR</b>, (Our <i dlt-r="2">x</i> <i dlt-r="3">y</i> OFFER)</p>',
            'sourceXml' => '<p>Our<strong>new</strong>offer, FOO<b>BAR</b>, (Our<i>x</i> <i>y</i>offer)</p>',
            'expectedXml' => '<p>Our<strong dlt-r="0">new</strong>offer, FOO<b dlt-r="1">BAR</b>, (Our<i dlt-r="2">x</i> <i dlt-r="3">y</i>OFFER)</p>',
        ];
        yield 'an ordinal keeps its glue to the number' => [
            'xml' => '<p>Le 1 <sup dlt-r="0">er</sup> étage</p>',
            'sourceXml' => '<p>The 1<sup>st</sup> floor</p>',
            'expectedXml' => '<p>Le 1<sup dlt-r="0">er</sup> étage</p>',
        ];
        yield 'issue 311 to German, translated words stay apart' => [
            'xml' => '<p>Unser <strong dlt-r="0">neues</strong> Angebot startet heute: die <em dlt-r="1">erweiterte</em> Garantie,'
                . ' die kostenlose <a href="t3://page?uid=40" dlt-r="2">Lieferung</a> und die <u dlt-r="3">persönliche</u> Beratung.</p>',
            'sourceXml' => '<p>Our<strong>new</strong>offer starts today: the<em>extended</em>warranty, the free<a href="t3://page?uid=40">delivery</a>and the<u>personal</u>advice.</p>',
            'expectedXml' => '<p>Unser <strong dlt-r="0">neues</strong> Angebot startet heute: die <em dlt-r="1">erweiterte</em> Garantie,'
                . ' die kostenlose <a href="t3://page?uid=40" dlt-r="2">Lieferung</a> und die <u dlt-r="3">persönliche</u> Beratung.</p>',
        ];
        yield 'compounds of a German source translated to two words are not fused' => [
            'xml' => '<p>Our product <strong dlt-r="0">news</strong> are here, the <strong dlt-r="1">shipping</strong> costs are low.</p>',
            'sourceXml' => '<p>Unsere Produkt<strong>neuheiten</strong> sind da, die <strong>Versand</strong>kosten sind niedrig.</p>',
            'expectedXml' => '<p>Our product <strong dlt-r="0">news</strong> are here, the <strong dlt-r="1">shipping</strong> costs are low.</p>',
        ];
        yield 'cognate compounds: both words unchanged are glued again, one changed word keeps them apart' => [
            'xml' => '<p>Our online <strong dlt-r="0">shop</strong> is open, your hotel <em dlt-r="1">room</em> is ready, the team <b dlt-r="2">leader</b> is coming,'
                . ' our <strong dlt-r="3">customer</strong> service helps.</p>',
            'sourceXml' => '<p>Unser Online<strong>shop</strong> ist offen, Ihr Hotel<em>zimmer</em> ist bereit, der Team<b>leiter</b> kommt,'
                . ' unser <strong>Kunden</strong>service hilft.</p>',
            'expectedXml' => '<p>Our online<strong dlt-r="0">shop</strong> is open, your hotel <em dlt-r="1">room</em> is ready, the team <b dlt-r="2">leader</b> is coming,'
                . ' our <strong dlt-r="3">customer</strong> service helps.</p>',
        ];
        yield 'a sup or sub is glued again when only the word outside is unchanged' => [
            'xml' => '<p>x <sup dlt-r="0">n</sup> und H <sub dlt-r="1">Wasser</sub></p>',
            'sourceXml' => '<p>x<sup>k</sup> and H<sub>water</sub></p>',
            'expectedXml' => '<p>x<sup dlt-r="0">n</sup> und H<sub dlt-r="1">Wasser</sub></p>',
        ];
        yield 'a glued letter DeepL pulled into the element' => [
            'xml' => '<p>The <em dlt-r="0">iPhone</em> is new.</p>',
            'sourceXml' => '<p>Das i<em>Phone</em> ist neu.</p>',
            'expectedXml' => '<p>The <em dlt-r="0">iPhone</em> is new.</p>',
        ];
        yield 'punctuation next to the element stays apart' => [
            'xml' => '<p>Our „<strong dlt-r="0">new</strong>“ offer</p>',
            'sourceXml' => '<p>Our<strong>new</strong>offer</p>',
            'expectedXml' => '<p>Our „<strong dlt-r="0">new</strong>“ offer</p>',
        ];
        yield 'spaces of elements without glue are kept' => [
            'xml' => '<p>a <em dlt-r="0">b</em> c</p>',
            'sourceXml' => '<p>a <em>b</em> c</p>',
            'expectedXml' => '<p>a <em dlt-r="0">b</em> c</p>',
        ];
    }

    #[Test]
    #[DataProvider('revertDataProvider')]
    public function revertGluesWordsTheTranslationKept(string $xml, string $sourceXml, string $expectedXml): void
    {
        static::assertSame($expectedXml, $this->revert($xml, $sourceXml));
    }

    #[Test]
    public function wordChecksDoNotTreatAFinalLineFeedAsEnd(): void
    {
        static::assertFalse(InlineMarkup::endsWithWordCharacter("with\n"));
        static::assertSame('', InlineMarkup::trailingWord("with\n"));
        static::assertSame('with', InlineMarkup::trailingWord('a with'));
        static::assertSame('Phone', InlineMarkup::leadingWord('Phone, x'));
    }
}
