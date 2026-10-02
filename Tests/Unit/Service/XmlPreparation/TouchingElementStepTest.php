<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Service\XmlPreparation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\TouchingElementStep;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\XmlPreparationStepInterface;

#[CoversClass(TouchingElementStep::class)]
final class TouchingElementStepTest extends AbstractXmlPreparationStepTestCase
{
    protected function createSubject(): XmlPreparationStepInterface
    {
        return new TouchingElementStep();
    }

    public static function prepareDataProvider(): \Generator
    {
        yield 'issue 665, the space inside the first link keeps it apart' => [
            'xml' => '<p><a class="button" href="https://api.whatsapp.com/send?phone=123456789">Whatsapp </a><a class="button" href="tel:+491234567">Call</a><a href="t3://page?uid=40">Book</a></p>',
            'expectedXml' => '<p><a class="button" href="https://api.whatsapp.com/send?phone=123456789" dlt-r="0">Whatsapp </a>'
                . '<dlt-s class="button" href="tel:+491234567" dlt-r="1">Call</dlt-s><dlt-s href="t3://page?uid=40" dlt-r="2">Book</dlt-s></p>',
        ];
        yield 'links in running text and nested in formatting elements' => [
            'xml' => '<p>Reach us by <a href="#1">Whatsapp</a><a href="#2">phone</a> or</p><ul><li><strong><a href="#3">Contact</a></strong><em><a href="#4">Home</a></em></li></ul>',
            'expectedXml' => '<p>Reach us by <dlt-s href="#1" dlt-r="0">Whatsapp</dlt-s><dlt-s href="#2" dlt-r="1">phone</dlt-s> or</p>'
                . '<ul><li><dlt-s dlt-r="2"><a href="#3" dlt-r="3">Contact</a></dlt-s><dlt-s dlt-r="4"><a href="#4" dlt-r="5">Home</a></dlt-s></li></ul>',
        ];
        yield 'attributes with a namespace' => [
            'xml' => '<p><span lang="en" xml:lang="en">Call</span><span xml:lang="de">Buchen</span></p>',
            'expectedXml' => '<p><dlt-s lang="en" xml:lang="en" dlt-r="0">Call</dlt-s><dlt-s xml:lang="de" dlt-r="1">Buchen</dlt-s></p>',
        ];
        yield 'a line feed at the end of an element is no touching word' => [
            'xml' => "<p><a href=\"1\">Call\n</a><a href=\"2\">Book</a></p>",
            'expectedXml' => "<p><a href=\"1\" dlt-r=\"0\">Call\n</a><a href=\"2\" dlt-r=\"1\">Book</a></p>",
        ];
        yield 'elements separated by space, punctuation or a line break and empty elements stay' => [
            'xml' => '<p><b>a</b> <i>b</i><i>, c</i><br/><u>d</u><span/><s>e</s></p>',
            'expectedXml' => '<p><b dlt-r="0">a</b> <i dlt-r="1">b</i><i dlt-r="2">, c</i><br/><u dlt-r="3">d</u><span dlt-r="4"/><s dlt-r="5">e</s></p>',
        ];
    }

    #[Test]
    #[DataProvider('prepareDataProvider')]
    public function prepareRenamesTouchingElements(string $xml, string $expectedXml): void
    {
        $this->assertSame($expectedXml, $this->prepare($xml));
    }

    public static function revertDataProvider(): \Generator
    {
        yield 'issue 665, answer of DeepL to French' => [
            'xml' => '<p><dlt-s class="button" href="tel:+491234567" dlt-r="0">Appeler</dlt-s><dlt-s href="t3://page?uid=40" dlt-r="1">Réserver</dlt-s></p>',
            'sourceXml' => '<p><a class="button" href="tel:+491234567">Call</a><a href="t3://page?uid=40">Book</a></p>',
            'expectedXml' => '<p><a dlt-r="0" class="button" href="tel:+491234567">Appeler</a><a dlt-r="1" href="t3://page?uid=40">Réserver</a></p>',
        ];
        yield 'nested, with a namespace' => [
            'xml' => '<li><dlt-s dlt-r="0"><a href="#3" dlt-r="1">Kontakt</a></dlt-s><dlt-s xml:lang="de" dlt-r="2">Start</dlt-s></li>',
            'sourceXml' => '<li><strong><a href="#3">Contact</a></strong><span xml:lang="de">Home</span></li>',
            'expectedXml' => '<li><strong dlt-r="0"><a href="#3" dlt-r="1">Kontakt</a></strong><span dlt-r="2" xml:lang="de">Start</span></li>',
        ];
        yield 'attributes come from the source, not from the answer' => [
            'xml' => '<p><dlt-s dlt-r="0">Anruf</dlt-s><dlt-s class="x" href="https://example.org" dlt-r="1">Buchen</dlt-s></p>',
            'sourceXml' => '<p><a class="button" href="tel:+491234567">Call</a><a href="t3://page?uid=40">Book</a></p>',
            'expectedXml' => '<p><a dlt-r="0" class="button" href="tel:+491234567">Anruf</a><a dlt-r="1" href="t3://page?uid=40">Buchen</a></p>',
        ];
        yield 'an element without a known reference number is replaced by its content' => [
            'xml' => '<p><dlt-s href="1">Anruf</dlt-s> <dlt-s dlt-r="9">Buchen</dlt-s></p>',
            'sourceXml' => '<p><a href="1">Call</a><a href="2">Book</a></p>',
            'expectedXml' => '<p>Anruf Buchen</p>',
        ];
    }

    #[Test]
    #[DataProvider('revertDataProvider')]
    public function revertRestoresTheSourceNames(string $xml, string $sourceXml, string $expectedXml): void
    {
        $this->assertSame($expectedXml, $this->revert($xml, $sourceXml));
    }
}
