<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core;

use DeepL\DeepLException;
use DeepL\GlossaryEntries;
use DeepL\GlossaryInfo;
use DeepL\GlossaryLanguagePair;
use DeepL\Language;
use DeepL\TranslateTextOptions;
use DeepL\Usage;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use WebVision\Deepltranslate\Core\Domain\Dto\TranslatedTextResult;
use WebVision\Deepltranslate\Core\Exception\ApiKeyNotSetException;
use WebVision\Deepltranslate\Core\Exception\XmlConversionException;
use WebVision\Deepltranslate\Core\Service\HtmlXmlConverter;
use WebVision\Deepltranslate\Core\Service\HtmlXmlConverterInterface;

/**
 * @internal No public usage
 */
#[AsAlias(id: ClientInterface::class, public: true)]
final class Client extends AbstractClient
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
     * `$htmlXmlConverter` is optional only to keep `new Client($configuration)` working.
     */
    public function __construct(
        ConfigurationInterface $configuration,
        private readonly HtmlXmlConverterInterface $htmlXmlConverter = new HtmlXmlConverter(),
    ) {
        parent::__construct($configuration);
    }

    /**
     * Sends the content as XML, because the HTML tag handling of DeepL merges adjacent inline elements
     * and joins `<br>` separated lines. The result is returned as HTML again, with the links of the source the
     * translation lost, which are logged as well.
     *
     * @return TranslatedTextResult|null
     *
     * @throws ApiKeyNotSetException
     */
    public function translate(
        string $content,
        ?string $sourceLang,
        string $targetLang,
        string $glossary = '',
        string $formality = ''
    ) {
        try {
            $result = $this->getTranslator()->translateText(
                $this->htmlXmlConverter->htmlToXml($content),
                $sourceLang,
                $targetLang,
                $this->buildTranslateOptions($content, $glossary, $formality)
            );
            $converted = $this->htmlXmlConverter->xmlToHtml($result->text, $content);
            foreach ($converted->lostLinks as $lostLink) {
                $this->logger->warning(
                    'A link of the source content is missing in its translation to {targetLanguage}, it has to be added again: {href} on "{text}".',
                    ['targetLanguage' => $targetLang, 'href' => $lostLink->href, 'text' => $lostLink->text]
                );
            }
            return new TranslatedTextResult($result, $converted->html, $converted->lostLinks);
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
    private function buildTranslateOptions(string $content, string $glossary, string $formality): array
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
        ];
        if ($tags['non_splitting_tags'] !== []) {
            $options[TranslateTextOptions::NON_SPLITTING_TAGS] = $tags['non_splitting_tags'];
        }
        if (!empty($glossary)) {
            $options[TranslateTextOptions::GLOSSARY] = $glossary;
        }
        return $options;
    }

    /**
     * @return Language[]
     *
     * @throws ApiKeyNotSetException
     */
    public function getSupportedLanguageByType(string $type = 'target'): array
    {
        try {
            return ($type === 'target')
                ? $this->getTranslator()->getTargetLanguages()
                : $this->getTranslator()->getSourceLanguages();
        } catch (DeepLException $exception) {
            $this->logger->error(sprintf(
                '%s (%d)',
                $exception->getMessage(),
                $exception->getCode()
            ));
        }

        return [];
    }

    /**
     * @return GlossaryLanguagePair[]
     *
     * @throws ApiKeyNotSetException
     */
    public function getGlossaryLanguagePairs(): array
    {
        try {
            return $this->getTranslator()->getGlossaryLanguages();
        } catch (DeepLException $exception) {
            $this->logger->error(sprintf(
                '%s (%d)',
                $exception->getMessage(),
                $exception->getCode()
            ));
        }

        return [];
    }

    /**
     * @return GlossaryInfo[]
     *
     * @throws ApiKeyNotSetException
     */
    public function getAllGlossaries(): array
    {
        try {
            return $this->getTranslator()->listGlossaries();
        } catch (DeepLException $exception) {
            $this->logger->error(sprintf(
                '%s (%d)',
                $exception->getMessage(),
                $exception->getCode()
            ));
        }

        return [];
    }

    /**
     * @throws ApiKeyNotSetException
     */
    public function getGlossary(string $glossaryId): ?GlossaryInfo
    {
        try {
            return $this->getTranslator()->getGlossary($glossaryId);
        } catch (DeepLException $exception) {
            $this->logger->error(sprintf(
                '%s (%d)',
                $exception->getMessage(),
                $exception->getCode()
            ));
        }

        return null;
    }

    /**
     * @param array<int, array{source: string, target: string}> $entries
     *
     * @throws ApiKeyNotSetException
     */
    public function createGlossary(
        string $glossaryName,
        string $sourceLang,
        string $targetLang,
        array $entries
    ): GlossaryInfo {
        $prepareEntriesForGlossary = [];
        foreach ($entries as $entry) {
            /*
             * as the version without trimming in TCA is already published,
             * we trim a second time here
             * to avoid errors in DeepL client
             */
            $source = trim($entry['source']);
            $target = trim($entry['target']);
            if (empty($source) || empty($target)) {
                continue;
            }
            $prepareEntriesForGlossary[$source] = $target;
        }
        try {
            return $this->getTranslator()->createGlossary(
                $glossaryName,
                $sourceLang,
                $targetLang,
                GlossaryEntries::fromEntries($prepareEntriesForGlossary)
            );
        } catch (DeepLException $e) {
            return new GlossaryInfo(
                '',
                '',
                false,
                '',
                '',
                new \DateTime(),
                0
            );
        }
    }

    /**
     * @throws ApiKeyNotSetException
     */
    public function deleteGlossary(string $glossaryId): void
    {
        try {
            $this->getTranslator()->deleteGlossary($glossaryId);
        } catch (DeepLException $exception) {
            $this->logger->error(sprintf(
                '%s (%d)',
                $exception->getMessage(),
                $exception->getCode()
            ));
        }
    }

    /**
     * @throws ApiKeyNotSetException
     */
    public function getGlossaryEntries(string $glossaryId): ?GlossaryEntries
    {
        try {
            return $this->getTranslator()->getGlossaryEntries($glossaryId);
        } catch (DeepLException $exception) {
            $this->logger->error(sprintf(
                '%s (%d)',
                $exception->getMessage(),
                $exception->getCode()
            ));
        }

        return null;
    }

    /**
     * @throws ApiKeyNotSetException
     */
    public function getUsage(): ?Usage
    {
        try {
            return $this->getTranslator()->getUsage();
        } catch (DeepLException $exception) {
            $this->logger->error(sprintf(
                '%s (%d)',
                $exception->getMessage(),
                $exception->getCode()
            ));
        }

        return null;
    }
}
