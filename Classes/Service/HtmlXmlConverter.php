<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use Masterminds\HTML5;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use WebVision\Deepltranslate\Core\Exception\XmlConversionException;

#[AsAlias(id: HtmlXmlConverterInterface::class, public: true)]
final class HtmlXmlConverter implements HtmlXmlConverterInterface
{
    private const ROOT_ELEMENT = 'deepltranslate-root';

    public function htmlToXml(string $html): string
    {
        if ($html === '') {
            return '';
        }
        $document = new \DOMDocument('1.0', 'UTF-8');
        $fragment = $this->createHtml5()->loadHTMLFragment($this->escapeLiteralLessThanSigns($html));
        $xml = '';
        foreach ($fragment->childNodes as $node) {
            $xml .= $document->saveXML($document->importNode($node, true));
        }
        return $xml;
    }

    public function xmlToHtml(string $xml): string
    {
        if ($xml === '') {
            return '';
        }
        $document = new \DOMDocument('1.0', 'UTF-8');
        $useInternalErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadXML(
            sprintf('<%1$s>%2$s</%1$s>', self::ROOT_ELEMENT, $xml),
            LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);
        if ($loaded === false || $document->documentElement === null) {
            throw new XmlConversionException('The translated content is not well-formed XML.', 1789723672);
        }
        $html5 = $this->createHtml5();
        $html = '';
        foreach ($document->documentElement->childNodes as $node) {
            $html .= $html5->saveHTML($node);
        }
        return $html;
    }

    /**
     * A `<` not followed by a letter, `!`, `/` or `?` is text according to the HTML5 tokenizer, but
     * masterminds/html5 drops it, which would lose it in plain text fields like "Kinder < 12 Jahre".
     */
    private function escapeLiteralLessThanSigns(string $html): string
    {
        return (string)preg_replace('#<(?![a-zA-Z!/?])#', '&lt;', $html);
    }

    private function createHtml5(): HTML5
    {
        return new HTML5([
            'disable_html_ns' => true,
        ]);
    }
}
