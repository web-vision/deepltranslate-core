<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Domain\Dto;

use DeepL\TextResult;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use WebVision\Deepltranslate\Core\Service\LostLink;

/**
 * The result of DeepL with the translation converted back into the format of the field, and the links of the
 * source the translation lost, see {@see \WebVision\Deepltranslate\Core\Translator::translate()}.
 */
#[Exclude]
final class TranslatedTextResult extends TextResult
{
    /**
     * @param list<LostLink> $lostLinks
     * @param int|null $billedCharacters the characters of the whole request, if more than the text of `$result`
     *                                   was sent, like the attribute texts of the content
     */
    public function __construct(
        TextResult $result,
        string $text,
        public readonly array $lostLinks = [],
        ?int $billedCharacters = null,
    ) {
        parent::__construct($text, $result->detectedSourceLang, $billedCharacters ?? $result->billedCharacters, $result->modelTypeUsed);
    }
}
