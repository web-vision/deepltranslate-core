<?php

namespace WebVision\Deepltranslate\Core;

use DeepL\DeepLException;
use DeepL\TextResult;
use DeepL\TranslateTextOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use WebVision\Deepltranslate\Core\Client\DeepLClientFactoryInterface;
use WebVision\Deepltranslate\Core\Domain\Dto\TranslatedTextResult;
use WebVision\Deepltranslate\Core\Exception\XmlConversionException;
use WebVision\Deepltranslate\Core\Service\HtmlXmlConverter;
use WebVision\Deepltranslate\Core\Service\HtmlXmlConverterInterface;

/**
 * Implementation for translation tasks.
 * @internal and not part of public API.
 */
#[AsAlias(id: TranslatorInterface::class, public: true)]
#[AsAlias(id: ContextAwareTranslatorInterface::class, public: true)]
final class Translator extends AbstractClient implements ContextAwareTranslatorInterface
{
    /**
     * Elements holding code. DeepL translates strings in them with the XML tag handling, for example
     * `var label = "Opening hours"`, `ignore_tags` keeps their content as it is.
     */
    private const IGNORE_TAGS = [
        'script',
        'style',
    ];

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
     * The number of texts DeepL takes in one request, see https://developers.deepl.com/api-reference/translate.
     */
    private const MAXIMUM_TEXTS_PER_REQUEST = 50;

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
     * and joins `<br>` separated lines. The result is returned as HTML again, with the links of the source the
     * translation lost, which are logged as well.
     *
     * The values of attributes readers see as text, like the title of a link, are sent as further texts of the
     * same request, DeepL translates no attribute values (issue #427). Their characters are billed as well. More
     * texts than DeepL takes in one request are sent in further requests.
     *
     * The context describes the content for DeepL, it is sent with each request and its characters are not billed.
     */
    public function translate(
        string $content,
        ?string $sourceLang,
        string $targetLang,
        string $glossary = '',
        string $formality = '',
        string $context = '',
    ): ?TranslatedTextResult {
        try {
            $xml = $this->htmlXmlConverter->htmlToXml($content);
            $attributeTexts = $this->htmlXmlConverter->getAttributeTexts($content);
            $options = $this->buildOptions($content, $glossary, $formality, $context);
            $results = [];
            // A failing request of further attribute texts fails the field, like a failing request of the content.
            foreach (array_chunk([$xml, ...$attributeTexts], self::MAXIMUM_TEXTS_PER_REQUEST) as $texts) {
                $result = $this->client()->translateText($attributeTexts === [] ? $xml : $texts, $sourceLang, $targetLang, $options);
                $results = [...$results, ...(is_array($result) ? array_values($result) : [$result])];
            }
            $contentResult = $results[0];
            $attributeResults = array_slice($results, 1);
            $converted = $this->htmlXmlConverter->xmlToHtml(
                $contentResult->text,
                $content,
                array_map(static fn(TextResult $attributeResult): string => $attributeResult->text, $attributeResults)
            );
            foreach ($converted->lostLinks as $lostLink) {
                $this->logger->warning(
                    'A link of the source content is missing in its translation to {targetLanguage}, it has to be added again: {href} on "{text}".',
                    ['targetLanguage' => $targetLang, 'href' => $lostLink->href, 'text' => $lostLink->text]
                );
            }
            return new TranslatedTextResult(
                $contentResult,
                $converted->html,
                $converted->lostLinks,
                array_sum(array_map(static fn(TextResult $textResult): int => $textResult->billedCharacters, [$contentResult, ...$attributeResults]))
            );
        } catch (DeepLException|XmlConversionException $exception) {
            // The caller knows the record and logs it, see TranslateHook.
            $this->logger->error(
                sprintf('%s (%d)', $exception->getMessage(), $exception->getCode()),
                ['sourceLanguage' => $sourceLang, 'targetLanguage' => $targetLang, 'contentLength' => mb_strlen($content)]
            );
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildOptions(string $content, string $glossary, string $formality, string $context): array
    {
        // The converter knows the tags its XML needs, for example the inline elements as `non_splitting_tags`,
        // without them DeepL dropped the main clause around a link in issue #489.
        $tags = $this->htmlXmlConverter->getTagHandlingOptions($content);
        $options = [
            // @todo Make this configurable, either as global setting or dependency injection (factory?) / event
            TranslateTextOptions::FORMALITY => $formality ?: 'default',
            TranslateTextOptions::TAG_HANDLING => 'xml',
            TranslateTextOptions::TAG_HANDLING_VERSION => 'v2',
            TranslateTextOptions::SPLITTING_TAGS => [...self::SPLITTING_TAGS, ...$tags['splitting_tags']],
            TranslateTextOptions::IGNORE_TAGS => self::IGNORE_TAGS,
        ];
        if ($tags['non_splitting_tags'] !== []) {
            $options[TranslateTextOptions::NON_SPLITTING_TAGS] = $tags['non_splitting_tags'];
        }
        if (!empty($glossary)) {
            $options[TranslateTextOptions::GLOSSARY] = $glossary;
        }
        if ($context !== '') {
            $options[TranslateTextOptions::CONTEXT] = $context;
        }
        return $options;
    }
}
