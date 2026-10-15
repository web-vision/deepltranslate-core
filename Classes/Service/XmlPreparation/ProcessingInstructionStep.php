<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service\XmlPreparation;

/**
 * Sends every processing instruction of the content, an XML node like `<?php echo 1; ?>` or the declaration
 * `<?xml version="1.0"?>` of an inline SVG (not the {@see \WebVision\Deepltranslate\Core\Service\ProcessingInstruction}
 * of a DataHandler command), as an empty placeholder element with its number: `a<?php echo 1; ?>b` becomes
 * `a<dlt-i dlt-n="0"/>b`.
 *
 * XML allows a declaration only at the start of a document and DeepL rejects the field otherwise, so
 * {@see StrictXmlStep} removed them, and the translation lost them (DPL-268). A placeholder holds no text, so
 * DeepL places it next to the translation of the words before or after it, and nothing of the instruction is
 * translated. The revert puts the processing instruction of the source back in place of its placeholder, taken
 * from the {@see PreparationRecord}.
 *
 * The instruction comes back as the serializer writes it, target and data separated by one space:
 * `<?php   echo 1;?>` becomes `<?php echo 1;?>`. `<?= $name ?>` and `<?php?>` are no processing instructions to
 * masterminds/html5, it reads them as comments, see the changelog. A placeholder DeepL drops loses its
 * instruction, like {@see ScriptPlaceholderStep}. A placeholder inside a word, `Fahr<?php x ?><em>rad</em>`, is no
 * word character for {@see GluedWordStep} and {@see TouchingElementStep}.
 *
 * Content that uses a helper name is sent without this step, its processing instructions are removed by
 * {@see StrictXmlStep}.
 *
 * @internal
 */
final class ProcessingInstructionStep implements XmlPreparationStepInterface
{
    public function prepare(\DOMElement $content, PreparationRecord $record): void
    {
        $document = DomTree::document($content);
        foreach (DomTree::query($content, './/processing-instruction()') as $number => $instruction) {
            if (!$instruction instanceof \DOMProcessingInstruction) {
                continue;
            }
            $record->rememberInstruction($number, $instruction->target, $instruction->data);
            $placeholder = $document->createElement(InlineMarkup::INSTRUCTION_ELEMENT);
            $placeholder->setAttribute(InlineMarkup::INSTRUCTION_ATTRIBUTE, (string)$number);
            $instruction->parentNode?->replaceChild($placeholder, $instruction);
        }
    }

    /**
     * Each processing instruction comes back once, at its first placeholder. A copy DeepL made and a placeholder
     * without a known number are removed. Text DeepL put into a placeholder is kept after it.
     */
    public function revert(\DOMElement $translation, PreparationRecord $record): void
    {
        $document = DomTree::document($translation);
        $restored = [];
        foreach (DomTree::elements($translation) as $placeholder) {
            if ($placeholder->localName !== InlineMarkup::INSTRUCTION_ELEMENT) {
                continue;
            }
            $number = $placeholder->getAttribute(InlineMarkup::INSTRUCTION_ATTRIBUTE);
            $instruction = preg_match('/^[0-9]+$/', $number) === 1 ? $record->instruction((int)$number) : null;
            $replacement = $document->createDocumentFragment();
            if ($instruction !== null && !isset($restored[(int)$number])) {
                $replacement->appendChild($document->createProcessingInstruction($instruction['target'], $instruction['data']));
                $restored[(int)$number] = true;
            }
            while ($placeholder->firstChild !== null) {
                $replacement->appendChild($placeholder->firstChild);
            }
            if ($replacement->hasChildNodes()) {
                $placeholder->parentNode?->replaceChild($replacement, $placeholder);
            } else {
                $placeholder->parentNode?->removeChild($placeholder);
            }
        }
    }
}
