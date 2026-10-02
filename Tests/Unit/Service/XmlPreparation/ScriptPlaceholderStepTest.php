<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Service\XmlPreparation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\ScriptPlaceholderStep;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\XmlPreparationStepInterface;

#[CoversClass(ScriptPlaceholderStep::class)]
final class ScriptPlaceholderStepTest extends AbstractXmlPreparationStepTestCase
{
    protected function createSubject(): XmlPreparationStepInterface
    {
        return new ScriptPlaceholderStep();
    }

    public static function prepareDataProvider(): \Generator
    {
        yield 'issue 311, symbol in sup' => [
            'xml' => '<p>Try the new FOOBAR<sup>®</sup> now!</p>',
            'expectedXml' => '<p>Try the new FOOBAR<dlt-p dlt-r="0"/> now!</p>',
        ];
        yield 'digits, punctuation and attributes' => [
            'xml' => '<p>a<sup class="fn" title="&quot;1&quot; &amp; more">1)</sup> b<sub>*</sub></p>',
            'expectedXml' => '<p>a<dlt-p dlt-r="0"/> b<dlt-p dlt-r="1"/></p>',
        ];
        yield 'letters, more than four characters, spaces, child elements and empty elements stay' => [
            'xml' => '<p>1<sup>st</sup> 2<sup>12345</sup> 3<sup>® </sup> 4<sup><b>®</b></sup> 5<sup/> 6<sup>Note</sup></p>',
            'expectedXml' => '<p>1<sup dlt-r="0">st</sup> 2<sup dlt-r="1">12345</sup> 3<sup dlt-r="2">® </sup> 4<sup dlt-r="3"><b dlt-r="4">®</b></sup> 5<sup dlt-r="5"/> 6<sup dlt-r="6">Note</sup></p>',
        ];
    }

    #[Test]
    #[DataProvider('prepareDataProvider')]
    public function prepareReplacesShortScriptElementsByPlaceholders(string $xml, string $expectedXml): void
    {
        static::assertSame($expectedXml, $this->prepare($xml));
    }

    public static function revertDataProvider(): \Generator
    {
        yield 'issue 311, answer of DeepL to German' => [
            'xml' => '<p>Probieren Sie jetzt den neuen FOOBAR<dlt-p dlt-r="0"/> aus!</p>',
            'sourceXml' => '<p>Try the new FOOBAR<sup>®</sup> now!</p>',
            'expectedXml' => '<p>Probieren Sie jetzt den neuen FOOBAR<sup>®</sup> aus!</p>',
        ];
        yield 'placeholder with end tag, the source element keeps its attributes' => [
            'xml' => '<p>a<dlt-p dlt-r="0"></dlt-p> b</p>',
            'sourceXml' => '<p>a<sup class="fn" title="&quot;1&quot; &amp; more">1)</sup> b</p>',
            'expectedXml' => '<p>a<sup class="fn" title="&quot;1&quot; &amp; more">1)</sup> b</p>',
        ];
        yield 'text DeepL put into a placeholder is kept after it' => [
            'xml' => '<p>FOOBAR<dlt-p dlt-r="0">jetzt</dlt-p></p>',
            'sourceXml' => '<p>FOOBAR<sup>®</sup></p>',
            'expectedXml' => '<p>FOOBAR<sup>®</sup>jetzt</p>',
        ];
        yield 'placeholder without or with an unknown reference number is removed' => [
            'xml' => '<p>a<dlt-p/> b<dlt-p dlt-r="7"/> c</p>',
            'sourceXml' => '<p>a<sup>®</sup> b</p>',
            'expectedXml' => '<p>a b c</p>',
        ];
        yield 'markup DeepL would put into an attribute is not used' => [
            'xml' => '<p>FOOBAR<dlt-p dlt-r="0" markup="&lt;script&gt;x&lt;/script&gt;"/></p>',
            'sourceXml' => '<p>FOOBAR<sup>®</sup></p>',
            'expectedXml' => '<p>FOOBAR<sup>®</sup></p>',
        ];
    }

    #[Test]
    #[DataProvider('revertDataProvider')]
    public function revertRestoresTheSourceElement(string $xml, string $sourceXml, string $expectedXml): void
    {
        static::assertSame($expectedXml, $this->revert($xml, $sourceXml));
    }
}
