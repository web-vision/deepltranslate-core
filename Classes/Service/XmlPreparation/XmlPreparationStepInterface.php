<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service\XmlPreparation;

/**
 * One change of the content before it is sent to DeepL, and the way back for the translation.
 *
 * Implementations are stateless. The XML carries only reference numbers (see {@see ReferenceStep}), whatever
 * else the revert needs is written into the {@see PreparationRecord} while preparing. The steps work on a wrapper
 * element holding the content as children, see {@see \WebVision\Deepltranslate\Core\Service\HtmlXmlConverter},
 * which runs them in a fixed order and reverts them in the opposite order.
 *
 * @internal
 */
interface XmlPreparationStepInterface
{
    /**
     * Changes the content that is about to be sent to DeepL and records what the revert needs.
     */
    public function prepare(\DOMElement $content, PreparationRecord $record): void;

    /**
     * Reverts {@see self::prepare()} in the translation DeepL returned. `$record` holds what the preparation of
     * the source content recorded.
     */
    public function revert(\DOMElement $translation, PreparationRecord $record): void;
}
