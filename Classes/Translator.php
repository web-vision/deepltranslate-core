<?php

namespace WebVision\Deepltranslate\Core;

use DeepL\DeepLException;
use DeepL\TextResult;
use DeepL\TranslateTextOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use WebVision\Deepltranslate\Core\Client\DeepLClientFactoryInterface;
use WebVision\Deepltranslate\Core\Exception\XmlConversionException;
use WebVision\Deepltranslate\Core\Service\HtmlXmlConverter;
use WebVision\Deepltranslate\Core\Service\HtmlXmlConverterInterface;

/**
 * Implementation for translation tasks.
 * @internal and not part of public API.
 */
#[AsAlias(id: TranslatorInterface::class, public: true)]
final class Translator extends AbstractClient implements TranslatorInterface
{
    /**
     * Elements starting a new sentence. Without them DeepL joins the lines of a `<br>` separated address
     * into one sentence and adds punctuation, and moves text between list items and table cells.
     */
    private const SPLITTING_TAGS = [
        'br',
        'p',
        'div',
        'li',
        'dt',
        'dd',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'td',
        'th',
        'caption',
        'blockquote',
        'figcaption',
        'address',
        'pre',
    ];

    /**
     * @internal
     * @todo typo3/cms-core:>=13.4.29 Replace constructor with `inject*()` methods in {@see AbstractClient},
     *       link: https://review.typo3.org/c/Packages/TYPO3.CMS/+/89244
     *
     * `$htmlXmlConverter` is optional only because {@see ClientInterface} fixes the constructor signature.
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected DeepLClientFactoryInterface $clientFactory,
        private readonly HtmlXmlConverterInterface $htmlXmlConverter = new HtmlXmlConverter(),
    ) {}

    /**
     * Sends the content as XML, because the HTML tag handling of DeepL merges adjacent inline elements
     * and joins `<br>` separated lines. The result is returned as HTML again.
     *
     * @return TextResult|TextResult[]|null
     */
    public function translate(
        string $content,
        ?string $sourceLang,
        string $targetLang,
        string $glossary = '',
        string $formality = '',
    ): array|null|TextResult {
        try {
            $result = $this->client()->translateText(
                $this->htmlXmlConverter->htmlToXml($content),
                $sourceLang,
                $targetLang,
                $this->buildOptions($glossary, $formality)
            );
            foreach (is_array($result) ? $result : [$result] as $textResult) {
                $textResult->text = $this->htmlXmlConverter->xmlToHtml($textResult->text);
            }
            return $result;
        } catch (DeepLException|XmlConversionException $exception) {
            $this->logger->error(sprintf(
                '%s (%d)',
                $exception->getMessage(),
                $exception->getCode()
            ));
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildOptions(string $glossary, string $formality): array
    {
        $options = [
            // @todo Make this configurable, either as global setting or dependency injection (factory?) / event
            TranslateTextOptions::FORMALITY => $formality ?: 'default',
            TranslateTextOptions::TAG_HANDLING => 'xml',
            TranslateTextOptions::TAG_HANDLING_VERSION => 'v2',
            TranslateTextOptions::SPLITTING_TAGS => self::SPLITTING_TAGS,
        ];
        if (!empty($glossary)) {
            $options[TranslateTextOptions::GLOSSARY] = $glossary;
        }
        return $options;
    }
}
