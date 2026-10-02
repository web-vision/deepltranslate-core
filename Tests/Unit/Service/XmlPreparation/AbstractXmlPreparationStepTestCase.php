<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Service\XmlPreparation;

use Masterminds\HTML5;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\PreparationRecord;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\ReferenceStep;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\XmlPreparationStepInterface;

/**
 * The steps work on a wrapper element holding the content, as {@see \WebVision\Deepltranslate\Core\Service\HtmlXmlConverter}
 * passes it. The tests write the content as XML. The reference numbers are set before the step under test, like
 * the converter does.
 */
abstract class AbstractXmlPreparationStepTestCase extends UnitTestCase
{
    abstract protected function createSubject(): XmlPreparationStepInterface;

    /**
     * @return list<XmlPreparationStepInterface>
     */
    protected function createPrecedingSteps(): array
    {
        return [new ReferenceStep()];
    }

    protected function prepare(string $xml): string
    {
        $content = $this->createContent($xml);
        $this->prepareContent($content, new PreparationRecord());
        return $this->serialize($content);
    }

    /**
     * Prepares the source to get the record, then reverts the translation with it.
     */
    protected function revert(string $xml, string $sourceXml = ''): string
    {
        $record = new PreparationRecord();
        $this->prepareContent($this->createContent($sourceXml), $record);
        $translation = $this->createContent($xml);
        $this->createSubject()->revert($translation, $record);
        return $this->serialize($translation);
    }

    protected function prepareContent(\DOMElement $content, PreparationRecord $record): void
    {
        foreach ([...$this->createPrecedingSteps(), $this->createSubject()] as $step) {
            $step->prepare($content, $record);
        }
    }

    /**
     * For content XML cannot hold, parsed like the converter parses HTML.
     */
    protected function createContentFromHtml(string $html): \DOMElement
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $content = $document->createElement('content');
        $document->appendChild($content);
        foreach ((new HTML5(['disable_html_ns' => true]))->loadHTMLFragment($html)->childNodes as $node) {
            $content->appendChild($document->importNode($node, true));
        }
        return $content;
    }

    protected function createContent(string $xml): \DOMElement
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        // With the encoding of the documents the converter creates, attribute values keep their characters.
        static::assertTrue($document->loadXML('<?xml version="1.0" encoding="UTF-8"?><content>' . $xml . '</content>'), $xml);
        static::assertInstanceOf(\DOMElement::class, $document->documentElement);
        return $document->documentElement;
    }

    protected function serialize(\DOMElement $content): string
    {
        $xml = '';
        foreach ($content->childNodes as $node) {
            $xml .= $content->ownerDocument?->saveXML($node);
        }
        return $xml;
    }
}
