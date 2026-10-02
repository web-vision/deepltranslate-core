<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Service\XmlPreparation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\ScriptDigitStep;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\XmlPreparationStepInterface;

#[CoversClass(ScriptDigitStep::class)]
final class ScriptDigitStepTest extends AbstractXmlPreparationStepTestCase
{
    protected function createSubject(): XmlPreparationStepInterface
    {
        return new ScriptDigitStep();
    }

    public static function prepareDataProvider(): \Generator
    {
        yield 'DPL-89, digits in sup and sub become script digits' => [
            'xml' => '<p>Hello1<sup>2</sup>, 25 m<sup>2</sup> of H<sub>2</sub>O, 2021<sup>1</sup>, 10<sup>3</sup> kg, <sup>1234</sup></p>',
            'expectedXml' => '<p>Hello1², 25 m² of H₂O, 2021¹, 10³ kg, ¹²³⁴</p>',
        ];
        yield 'content with a script digit of its own is untouched' => [
            'xml' => '<p>10² and m<sup>2</sup></p>',
            'expectedXml' => '<p>10² and m<sup dlt-r="0">2</sup></p>',
        ];
        yield 'elements with attributes, letters, symbols, more than four digits or child elements are untouched' => [
            'xml' => '<p>a<sup class="fn">1</sup> 1<sup>st</sup> X<sup>®</sup> 1<sup>12345</sup> 2<sup><b>3</b></sup> 4<sup> 5</sup></p>',
            'expectedXml' => '<p>a<sup class="fn" dlt-r="0">1</sup> 1<sup dlt-r="1">st</sup> X<sup dlt-r="2">®</sup> 1<sup dlt-r="3">12345</sup> 2<sup dlt-r="4"><b dlt-r="5">3</b></sup> 4<sup dlt-r="6"> 5</sup></p>',
        ];
        yield 'only the digit elements of mixed content' => [
            'xml' => '<p>m<sup>2</sup> and 1<sup>st</sup></p>',
            'expectedXml' => '<p>m² and 1<sup dlt-r="1">st</sup></p>',
        ];
    }

    #[Test]
    #[DataProvider('prepareDataProvider')]
    public function prepareReplacesDigitElements(string $xml, string $expectedXml): void
    {
        $this->assertSame($expectedXml, $this->prepare($xml));
    }

    public static function revertDataProvider(): \Generator
    {
        yield 'DPL-89, answer of DeepL to French' => [
            'xml' => '<p>Bonjour1², la pièce mesure 25 m², le réservoir contient 3 m³ d\'eau (H₂O), 2021¹, 2022² et 2023³.</p>',
            'sourceXml' => '<p>Hello1<sup>2</sup>, 25 m<sup>2</sup>, 3 m<sup>3</sup> (H<sub>2</sub>O), 2021<sup>1</sup>, 2022<sup>2</sup> and 2023<sup>3</sup>.</p>',
            'expectedXml' => '<p>Bonjour1<sup>2</sup>, la pièce mesure 25 m<sup>2</sup>, le réservoir contient 3 m<sup>3</sup> d\'eau (H<sub>2</sub>O), 2021<sup>1</sup>, 2022<sup>2</sup> et 2023<sup>3</sup>.</p>',
        ];
        yield 'runs of digits become one element, in nested elements too' => [
            'xml' => '<p><strong>x¹²</strong> y₁₀</p>',
            'sourceXml' => '<p><strong>x<sup>12</sup></strong> y<sub>10</sub></p>',
            'expectedXml' => '<p><strong>x<sup>12</sup></strong> y<sub>10</sub></p>',
        ];
        yield 'script digits stay characters if the source had script digits' => [
            'xml' => '<p>10² und m<sup>2</sup></p>',
            'sourceXml' => '<p>10² and m<sup>2</sup></p>',
            'expectedXml' => '<p>10² und m<sup>2</sup></p>',
        ];
        yield 'script digits stay characters if the source had no digit element' => [
            'xml' => '<p>25 m²</p>',
            'sourceXml' => '<p>25 square metres</p>',
            'expectedXml' => '<p>25 m²</p>',
        ];
    }

    #[Test]
    #[DataProvider('revertDataProvider')]
    public function revertTurnsScriptDigitsIntoElements(string $xml, string $sourceXml, string $expectedXml): void
    {
        $this->assertSame($expectedXml, $this->revert($xml, $sourceXml));
    }
}
