<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Service\XmlPreparation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\ElementCopyStep;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\XmlPreparationStepInterface;

#[CoversClass(ElementCopyStep::class)]
final class ElementCopyStepTest extends AbstractXmlPreparationStepTestCase
{
    private const ISSUE_489_XML = "<p>\n    <i>Mehr über mögliche Einsatzbereiche zeigt Ihnen gerne unsere </i><a href=\"t3://page?uid=21#5017\"><i>Studienberatung</i></a><i> auf.</i>\n</p>";

    protected function createSubject(): XmlPreparationStepInterface
    {
        return new ElementCopyStep();
    }

    #[Test]
    public function prepareChangesNothingButTheReferenceNumbers(): void
    {
        static::assertSame('<p>a <strong dlt-r="0">b</strong></p>', $this->prepare('<p>a <strong>b</strong></p>'));
    }

    /**
     * Answers DeepL gave with the inline elements in `non_splitting_tags`, recorded on 2026-10-01 (the helper names
     * of that time replaced by the current ones).
     */
    public static function revertDataProvider(): \Generator
    {
        yield 'issue 489 to English: an empty copy and a split element' => [
            'xml' => "<p>\n    <i dlt-r=\"0\">Our </i><a href=\"t3://page?uid=21#5017\" dlt-r=\"1\"><i dlt-r=\"2\">student advisory service</i></a><i dlt-r=\"3\"></i>\n"
                . "    <i dlt-r=\"0\">will be happy to tell you more about possible career paths</i><i dlt-r=\"3\">.</i>\n</p>",
            'sourceXml' => self::ISSUE_489_XML,
            'expectedXml' => "<p>\n    <i dlt-r=\"0\">Our </i><a href=\"t3://page?uid=21#5017\" dlt-r=\"1\"><i dlt-r=\"2\">student advisory service</i></a>\n"
                . "    <i dlt-r=\"0\">will be happy to tell you more about possible career paths.</i>\n</p>",
        ];
        yield 'issue 489 to French: a split element without empty copy' => [
            'xml' => "<p>\n    <i dlt-r=\"0\">Notre </i><a href=\"t3://page?uid=21#5017\" dlt-r=\"1\"><i dlt-r=\"2\">service d'orientation universitaire</i></a>\n    \n"
                . "    <i dlt-r=\"0\">se fera un plaisir de vous en dire plus sur les débouchés professionnels possibles</i><i dlt-r=\"3\">.</i>\n</p>",
            'sourceXml' => self::ISSUE_489_XML,
            'expectedXml' => "<p>\n    <i dlt-r=\"0\">Notre </i><a href=\"t3://page?uid=21#5017\" dlt-r=\"1\"><i dlt-r=\"2\">service d'orientation universitaire</i></a>\n    \n"
                . "    <i dlt-r=\"0\">se fera un plaisir de vous en dire plus sur les débouchés professionnels possibles.</i>\n</p>",
        ];
        yield 'issue 489 with HTML tag handling v1: copies apart from each other are kept' => [
            'xml' => '<p><i dlt-r="0">Our </i><a href="t3://page?uid=21#5017" dlt-r="1"><i dlt-r="2">careers advisory service</i></a>'
                . ' <i dlt-r="0">will be happy </i><i dlt-r="3"> to tell</i> <i dlt-r="0">you more about potential career paths </i><i dlt-r="3">.</i></p>',
            'sourceXml' => self::ISSUE_489_XML,
            'expectedXml' => '<p><i dlt-r="0">Our </i><a href="t3://page?uid=21#5017" dlt-r="1"><i dlt-r="2">careers advisory service</i></a>'
                . ' <i dlt-r="0">will be happy  to tell</i> <i dlt-r="0">you more about potential career paths .</i></p>',
        ];
        yield 'link copied three times keeps all copies, the text of each belongs to it' => [
            'xml' => '<p>der <a href="t3://page?uid=40" dlt-r="0">kostenlose</a> Versan<a href="t3://page?uid=40" dlt-r="0">d</a> un<a href="t3://page?uid=40" dlt-r="0">d</a> die</p>',
            'sourceXml' => '<p>the free<a href="t3://page?uid=40">delivery</a>and the</p>',
            'expectedXml' => '<p>der <a href="t3://page?uid=40" dlt-r="0">kostenlose</a> Versan<a href="t3://page?uid=40" dlt-r="0">d</a> un<a href="t3://page?uid=40" dlt-r="0">d</a> die</p>',
        ];
    }

    #[Test]
    #[DataProvider('revertDataProvider')]
    public function revertRepairsCopies(string $xml, string $sourceXml, string $expectedXml): void
    {
        static::assertSame($expectedXml, $this->revert($xml, $sourceXml));
    }

    public static function revertShapesDataProvider(): \Generator
    {
        yield 'whitespace only copy is replaced by its whitespace' => [
            'xml' => '<p><b dlt-r="0">a</b><b dlt-r="0"> </b>c</p>',
            'sourceXml' => '<p><b>a</b> c</p>',
            'expectedXml' => '<p><b dlt-r="0">a</b> c</p>',
        ];
        yield 'of copies without content the first is kept, a duplicated placeholder too' => [
            'xml' => '<p><span class="icon" dlt-r="0"/>a<span class="icon" dlt-r="0"/> FOOBAR<dlt-p dlt-r="1"/><dlt-p dlt-r="1"/></p>',
            'sourceXml' => '<p><span class="icon"></span>a FOOBAR<sup>®</sup></p>',
            'expectedXml' => '<p><span class="icon" dlt-r="0"/>a FOOBAR<dlt-p dlt-r="1"/></p>',
        ];
        yield 'copy holding an element is not empty' => [
            'xml' => '<p><a href="#" dlt-r="0"><img src="x"/></a> und <a href="#" dlt-r="0">Bild</a></p>',
            'sourceXml' => '<p><a href="#"><img src="x"/>Bild</a></p>',
            'expectedXml' => '<p><a href="#" dlt-r="0"><img src="x"/></a> und <a href="#" dlt-r="0">Bild</a></p>',
        ];
        yield 'copies of one element are merged, the first keeps its attributes' => [
            'xml' => '<p><span class="a" dlt-r="0">x</span><span class="b" dlt-r="0">y</span></p>',
            'sourceXml' => '<p><span class="a">xy</span></p>',
            'expectedXml' => '<p><span class="a" dlt-r="0">xy</span></p>',
        ];
        yield 'B1, French 12004: copies of one link around a space become one link' => [
            'xml' => '<p><a href="https://github.com/web-vision/deepltranslate-core/pull/670" dlt-r="3">Le</a> <a href="https://github.com/web-vision/deepltranslate-core/pull/670" dlt-r="3">ticket n° 670</a> traite séparément</p>',
            'sourceXml' => '<p><code>a</code> <code>b</code> <code>c</code> <a href="https://github.com/web-vision/deepltranslate-core/pull/670">#670</a> handles</p>',
            'expectedXml' => '<p><a href="https://github.com/web-vision/deepltranslate-core/pull/670" dlt-r="3">Le ticket n° 670</a> traite séparément</p>',
        ];
        yield 'copies of one link touching each other become one link' => [
            'xml' => '<p><a href="#" dlt-r="0">x</a><a href="#" dlt-r="0">y</a></p>',
            'sourceXml' => '<p><a href="#">xy</a></p>',
            'expectedXml' => '<p><a href="#" dlt-r="0">xy</a></p>',
        ];
        yield 'two different source links with whitespace or nothing between them are never merged' => [
            'xml' => '<p><a href="#" dlt-r="0">x</a> <a href="#" dlt-r="1">y</a><a href="#" dlt-r="2">z</a></p>',
            'sourceXml' => '<p><a href="#">x</a> <a href="#">y</a><a href="#">z</a></p>',
            'expectedXml' => '<p><a href="#" dlt-r="0">x</a> <a href="#" dlt-r="1">y</a><a href="#" dlt-r="2">z</a></p>',
        ];
        yield 'B2, German 14004: copies on separate words are kept' => [
            'xml' => '<p><strong dlt-r="0">Derzeit</strong> werden die Texte übersetzt, <strong dlt-r="0">während</strong> die Beschriftungen bleiben.</p>',
            'sourceXml' => '<p><strong>Today:</strong> the texts are translated, the labels stay.</p>',
            'expectedXml' => '<p><strong dlt-r="0">Derzeit</strong> werden die Texte übersetzt, <strong dlt-r="0">während</strong> die Beschriftungen bleiben.</p>',
        ];
        yield 'adjacent equal elements of the source are not merged' => [
            'xml' => '<p><strong dlt-r="0">A</strong><strong dlt-r="1">B</strong> c</p>',
            'sourceXml' => '<p><strong>A</strong><strong>B</strong> c</p>',
            'expectedXml' => '<p><strong dlt-r="0">A</strong><strong dlt-r="1">B</strong> c</p>',
        ];
        yield 'three adjacent pieces of a copied element become one' => [
            'xml' => '<p><em dlt-r="0">a</em><em dlt-r="1">b</em><em dlt-r="0">c</em></p>',
            'sourceXml' => '<p><em>a</em> <em>b</em></p>',
            'expectedXml' => '<p><em dlt-r="0">abc</em></p>',
        ];
        yield 'splitting elements are merged by the name of their source element' => [
            'xml' => '<p><dlt-s dlt-r="0">a</dlt-s><dlt-s dlt-r="0">b</dlt-s><dlt-s dlt-r="1">c</dlt-s></p>',
            'sourceXml' => '<p><strong>ab</strong><a href="#">c</a></p>',
            'expectedXml' => '<p><dlt-s dlt-r="0">ab</dlt-s><dlt-s dlt-r="1">c</dlt-s></p>',
        ];
        yield 'an empty copy is removed before the text around it is joined' => [
            'xml' => '<p>Product <strong dlt-r="0"></strong><strong dlt-r="0">news</strong> are here</p>',
            'sourceXml' => '<p>Produkt<strong>neuheiten</strong> sind da</p>',
            'expectedXml' => '<p>Product <strong dlt-r="0">news</strong> are here</p>',
        ];
    }

    #[Test]
    #[DataProvider('revertShapesDataProvider')]
    public function revertHandlesCopyShapes(string $xml, string $sourceXml, string $expectedXml): void
    {
        static::assertSame($expectedXml, $this->revert($xml, $sourceXml));
    }
}
