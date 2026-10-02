<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use Masterminds\HTML5;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use WebVision\Deepltranslate\Core\Exception\XmlConversionException;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\DomTree;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\ElementCopyStep;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\GluedWordStep;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\InlineMarkup;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\PreparationRecord;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\ReferenceStep;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\ScriptDigitStep;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\ScriptPlaceholderStep;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\StrictXmlStep;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\TouchingElementStep;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\XmlPreparationStepInterface;

/**
 * Besides the syntax, the content is prepared for the way DeepL places inline markup, see the steps in
 * {@see self::__construct()}. The XML carries reference numbers, everything else the revert needs is recorded
 * while preparing. The revert prepares the source content once more to get the same record, so nothing is kept
 * between the two calls.
 */
#[AsAlias(id: HtmlXmlConverterInterface::class, public: true)]
final readonly class HtmlXmlConverter implements HtmlXmlConverterInterface
{
    private const ROOT_ELEMENT = 'deepltranslate-root';

    /**
     * @var list<XmlPreparationStepInterface>
     */
    private array $steps;

    private StrictXmlStep $strictXmlStep;

    /**
     * The order is part of the preparation and therefore fixed:
     *
     * - the reference numbers are set first, on the content as it is stored,
     * - the `<sup>`/`<sub>` steps replace elements the later steps would otherwise mark or rename,
     * - the glue is set while touching elements still have their names,
     * - the characters XML does not allow are removed last, so every step decides on the stored content.
     *
     * The revert runs in the opposite order: copies are repaired first, so an empty copy cannot hide an element
     * from the other steps, and the reference numbers are removed last.
     */
    public function __construct()
    {
        $this->strictXmlStep = new StrictXmlStep();
        $this->steps = [
            new ReferenceStep(),
            new ScriptDigitStep(),
            new ScriptPlaceholderStep(),
            new GluedWordStep(),
            new TouchingElementStep(),
            new ElementCopyStep(),
            $this->strictXmlStep,
        ];
    }

    public function getTagHandlingOptions(string $html): array
    {
        // Content using a helper name is sent without preparation, so the inline elements must split as before.
        if (InlineMarkup::mayUseHelperNames($html) && $this->usesHelperNames($this->parseHtml($html))) {
            return ['splitting_tags' => [], 'non_splitting_tags' => []];
        }
        return [
            'splitting_tags' => [InlineMarkup::SPLITTING_ELEMENT],
            'non_splitting_tags' => [...InlineMarkup::ELEMENTS, InlineMarkup::PLACEHOLDER_ELEMENT],
        ];
    }

    public function htmlToXml(string $html): string
    {
        if ($html === '') {
            return '';
        }
        $content = $this->parseHtml($html);
        $record = new PreparationRecord();
        foreach ($this->stepsFor($content) as $step) {
            $step->prepare($content, $record);
        }
        $document = DomTree::document($content);
        $xml = '';
        foreach ($content->childNodes as $node) {
            $xml .= $document->saveXML($node);
        }
        return $xml;
    }

    public function xmlToHtml(string $xml, string $sourceHtml): ConvertedHtml
    {
        if ($xml === '') {
            return new ConvertedHtml('');
        }
        $translation = $this->parseXml($xml);
        $source = $this->parseHtml($sourceHtml);
        $steps = $this->stepsFor($source);
        $record = new PreparationRecord();
        foreach ($steps as $step) {
            $step->prepare($source, $record);
        }
        $lostLinks = [];
        if ($steps !== [$this->strictXmlStep]) {
            $this->assertTranslationBelongsToSource($translation, $record);
            $lostLinks = $this->findLostLinks($translation, $source, $sourceHtml, $record);
        }
        foreach (array_reverse($steps) as $step) {
            $step->revert($translation, $record);
        }
        $html5 = $this->createHtml5();
        $html = '';
        foreach ($translation->childNodes as $node) {
            $html .= $html5->saveHTML($node);
        }
        return new ConvertedHtml($html, $lostLinks);
    }

    /**
     * The reference numbers lead to the elements of `$sourceHtml`. If that is not the content that was sent, they
     * would lead to other elements and the revert would rename or replace the wrong ones.
     *
     * @throws XmlConversionException
     */
    private function assertTranslationBelongsToSource(\DOMElement $translation, PreparationRecord $record): void
    {
        foreach (DomTree::elements($translation) as $element) {
            $reference = InlineMarkup::referenceOf($element);
            if ($reference === null) {
                continue;
            }
            $name = $record->name($reference);
            $belongs = match ($element->localName) {
                InlineMarkup::SPLITTING_ELEMENT => $record->attributes($reference) !== null,
                InlineMarkup::PLACEHOLDER_ELEMENT => $record->element($reference) !== null,
                default => $element->localName === $name,
            };
            if (!$belongs) {
                throw new XmlConversionException(
                    sprintf(
                        'The translated content does not belong to the given source content: element <%s> has reference number %d, the source has %s there.',
                        (string)$element->localName,
                        $reference,
                        $name === null ? 'no element' : '<' . $name . '>'
                    ),
                    1790943110
                );
            }
        }
    }

    /**
     * DeepL can lose a link that covers only part of a word, when the translation is one word: `Fahr<a>rad</a>`
     * became `bicycle` in English, on the pull request before this preparation as well, in French too. The
     * translation is kept, it is correct apart from the link, and an untranslated field or an empty one would be
     * worse. The caller tells the editor which link to add again.
     *
     * Target and text of the link are taken from the source as stored, the preparation changes the text, for
     * example `Fahr<em>rad</em>` to `Fahr <em>rad</em>` or `<sup>2</sup>` to `²`.
     *
     * @return list<LostLink>
     */
    private function findLostLinks(
        \DOMElement $translation,
        \DOMElement $source,
        string $sourceHtml,
        PreparationRecord $record
    ): array {
        $found = [];
        foreach (DomTree::elements($translation) as $element) {
            $reference = InlineMarkup::referenceOf($element);
            if ($reference !== null) {
                $found[$reference] = true;
            }
        }
        $lostLinks = [];
        $storedElements = null;
        foreach (DomTree::elements($source) as $element) {
            $reference = InlineMarkup::referenceOf($element);
            if ($reference === null || isset($found[$reference]) || $record->name($reference) !== 'a') {
                continue;
            }
            // Parsed again without preparation, only when a link is lost.
            $storedElements ??= ReferenceStep::numberedElements($this->parseHtml($sourceHtml));
            $link = $storedElements[$reference] ?? $element;
            $lostLinks[] = new LostLink($link->getAttribute('href'), $link->textContent);
        }
        return $lostLinks;
    }

    /**
     * Content that already uses a name of the helper elements or attributes is sent without preparation, apart
     * from what XML does not allow: the revert could not tell its own markup apart.
     *
     * @return list<XmlPreparationStepInterface>
     */
    private function stepsFor(\DOMElement $content): array
    {
        return $this->usesHelperNames($content) ? [$this->strictXmlStep] : $this->steps;
    }

    private function usesHelperNames(\DOMElement $content): bool
    {
        return DomTree::query(
            $content,
            sprintf('.//*[starts-with(name(), "%1$s")] | .//@*[starts-with(name(), "%1$s")]', InlineMarkup::HELPER_PREFIX)
        ) !== [];
    }

    /**
     * @return \DOMElement a wrapper element holding the content as children
     */
    private function parseHtml(string $html): \DOMElement
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $content = $document->createElement(self::ROOT_ELEMENT);
        $document->appendChild($content);
        $fragment = $this->createHtml5()->loadHTMLFragment($this->escapeLiteralLessThanSigns($html));
        foreach ($fragment->childNodes as $node) {
            $content->appendChild($document->importNode($node, true));
        }
        return $content;
    }

    /**
     * @return \DOMElement a wrapper element holding the content as children
     * @throws XmlConversionException
     */
    private function parseXml(string $xml): \DOMElement
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $useInternalErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadXML(sprintf('<%1$s>%2$s</%1$s>', self::ROOT_ELEMENT, $xml), LIBXML_NONET);
        $errors = array_filter(
            libxml_get_errors(),
            static fn(\LibXMLError $error): bool => $error->level >= LIBXML_ERR_ERROR
        );
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);
        if ($loaded === false || $document->documentElement === null) {
            $error = reset($errors);
            throw new XmlConversionException(
                sprintf(
                    'The translated content is not well-formed XML: %s',
                    $error instanceof \LibXMLError ? trim($error->message) : ''
                ),
                1789723672
            );
        }
        return $document->documentElement;
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
