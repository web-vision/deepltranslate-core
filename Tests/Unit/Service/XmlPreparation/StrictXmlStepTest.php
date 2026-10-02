<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Service\XmlPreparation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\PreparationRecord;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\StrictXmlStep;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\XmlPreparationStepInterface;

#[CoversClass(StrictXmlStep::class)]
final class StrictXmlStepTest extends AbstractXmlPreparationStepTestCase
{
    protected function createSubject(): XmlPreparationStepInterface
    {
        return new StrictXmlStep();
    }

    protected function createPrecedingSteps(): array
    {
        return [];
    }

    public static function prepareDataProvider(): \Generator
    {
        yield 'vertical tab and form feed become a space' => [
            'html' => "<p>Zeile 1\u{B}Zeile 2\u{C}Seite 2</p>",
            'expectedXml' => '<p>Zeile 1 Zeile 2 Seite 2</p>',
        ];
        yield 'other control characters are removed, tab, newline and DEL are kept' => [
            'html' => "<p>a\u{1}b\u{8}c\u{1F}d\te\nf\u{7F}g</p>",
            'expectedXml' => "<p>abcd\te\nf\u{7F}g</p>",
        ];
        yield 'character references to control characters' => [
            'html' => '<p>a&#11;b&#1;c</p>',
            'expectedXml' => '<p>a bc</p>',
        ];
        yield 'noncharacters U+FFFE and U+FFFF are removed' => [
            'html' => "<p>a\u{FFFE}b&#xFFFF;c</p>",
            'expectedXml' => '<p>abc</p>',
        ];
        yield 'characters in attribute values, entities stay escaped' => [
            'html' => "<a href=\"t3://page?uid=1&amp;x=\u{B}\" title=\"a\u{1} &quot;b&quot; &amp; c\">Link</a>",
            'expectedXml' => '<a href="t3://page?uid=1&amp;x= " title="a &quot;b&quot; &amp; c">Link</a>',
        ];
        yield 'characters in attribute values with a namespace' => [
            'html' => "<p xml:lang=\"de\u{1}\">x</p>",
            'expectedXml' => '<p xml:lang="de">x</p>',
        ];
        yield 'processing instructions are removed' => [
            'html' => '<p>a<?xml version="1.0"?>b<?php echo 1; ?>c</p>',
            'expectedXml' => '<p>abc</p>',
        ];
        yield 'double hyphens and a trailing hyphen in comments are separated' => [
            'html' => "<p>a<!-- x -- y ---></p><!---z---><!-- ok- --><!--\u{B}-->",
            'expectedXml' => '<p>a<!-- x - - y - --></p><!---z- --><!-- ok- --><!-- -->',
        ];
        yield 'valid content is untouched' => [
            'html' => "<p title=\"Preis &amp; Leistung\">Preis:\u{A0}100\u{A0}€ &lt;b&gt; 😀<!-- Hinweis --></p>",
            'expectedXml' => "<p title=\"Preis &amp; Leistung\">Preis:\u{A0}100\u{A0}€ &lt;b&gt; 😀<!-- Hinweis --></p>",
        ];
    }

    #[Test]
    #[DataProvider('prepareDataProvider')]
    public function prepareReturnsWellFormedXml(string $html, string $expectedXml): void
    {
        $content = $this->createContentFromHtml($html);

        $this->createSubject()->prepare($content, new PreparationRecord());

        $xml = $this->serialize($content);
        $this->assertSame($expectedXml, $xml);
        $this->assertTrue((new \DOMDocument())->loadXML('<content>' . $xml . '</content>'), $xml);
    }

    #[Test]
    public function revertChangesNothing(): void
    {
        $this->assertSame('<p>a<!-- x - - y --></p>', $this->revert('<p>a<!-- x - - y --></p>', '<p>a</p>'));
    }
}
