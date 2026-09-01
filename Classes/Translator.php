<?php

namespace WebVision\Deepltranslate\Core;

use DeepL\DeepLException;
use DeepL\TextResult;
use DeepL\TranslateTextOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use WebVision\Deepltranslate\Core\Client\DeepLClientFactoryInterface;

/**
 * Implementation for translation tasks.
 * @internal and not part of public API.
 */
#[AsAlias(id: TranslatorInterface::class, public: true)]
final class Translator extends AbstractClient implements TranslatorInterface
{
    /**
     * @internal
     * @todo typo3/cms-core:>=13.4.29 Replace constructor with `inject*()` methods in {@see AbstractClient},
     *       link: https://review.typo3.org/c/Packages/TYPO3.CMS/+/89244
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected DeepLClientFactoryInterface $clientFactory,
    ) {}

    /**
     * @return TextResult|TextResult[]|null
     */
    public function translate(
        string $content,
        ?string $sourceLang,
        string $targetLang,
        string $glossary = '',
        string $formality = '',
    ): array|null|TextResult {
        $options = [
            // @todo Make this configurable, either as global setting or dependency injection (factory?) / event
            TranslateTextOptions::FORMALITY => $formality ?: 'default',
            // @todo Make this configurable, either as global setting or dependency injection (factory?) / event
            TranslateTextOptions::TAG_HANDLING => 'html',
            // @todo Make this configurable, either as global setting or dependency injection (factory?) / event
            TranslateTextOptions::TAG_HANDLING_VERSION => 'v2',
        ];

        if (!empty($glossary)) {
            $options[TranslateTextOptions::GLOSSARY] = $glossary;
        }

        $replaceLinks = false;
        if (preg_match('/<a [^<]+>/', $content, $m) != 0) {
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();

            $links = $dom->getElementsByTagName('a');
            $titlesToTranslate = [];

            foreach ($links as $link) {
                if ($link->hasAttribute('title')) {
                    $titlesToTranslate[] = $link->getAttribute('title');
                }
            }
            $content = array_merge([$content], $titlesToTranslate);
            $replaceLinks = true;
        }

        try {
            $translations = $this->client()->translateText(
                $content,
                $sourceLang,
                $targetLang,
                $options
            );

            if ($replaceLinks) {
                $translatedHtml = $translations[0]->text;
                $billedCharacters = $translations[0]->billedCharacters;

                $translatedTitles = [];
                for ($i = 1; $i < count($translations); $i++) {
                    $translatedTitles[] = $translations[$i]->text;
                    $billedCharacters += $translations[$i]->billedCharacters;
                }

                $domResult = new \DOMDocument();
                libxml_use_internal_errors(true);
                $domResult->loadHTML('<?xml encoding="utf-8" ?>' . $translatedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                libxml_clear_errors();

                $resultLinks = $domResult->getElementsByTagName('a');
                $titleIndex = 0;

                foreach ($resultLinks as $link) {
                    if ($link->hasAttribute('title') && isset($translatedTitles[$titleIndex])) {
                        $link->setAttribute('title', $translatedTitles[$titleIndex]);
                        $titleIndex++;
                    }
                }

                $html = $domResult->saveHTML();
                $translations = new TextResult($html, $translations[0]->detectedSourceLang, $billedCharacters, $translations[0]->modelTypeUsed);
            }
            return $translations;
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
