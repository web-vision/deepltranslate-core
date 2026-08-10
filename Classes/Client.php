<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core;

use DeepL\DeepLException;
use DeepL\GlossaryEntries;
use DeepL\GlossaryInfo;
use DeepL\GlossaryLanguagePair;
use DeepL\Language;
use DeepL\TextResult;
use DeepL\TranslateTextOptions;
use DeepL\Usage;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use WebVision\Deepltranslate\Core\Exception\ApiKeyNotSetException;

/**
 * @internal No public usage
 */
#[AsAlias(id: ClientInterface::class, public: true)]
final class Client extends AbstractClient
{
    /**
     * @return TextResult|TextResult[]|null
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
            $translations = $this->getTranslator()->translateText(
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

                // remove the processing instruction node libxml added
                foreach ($domResult->childNodes as $node) {
                    if ($node->nodeType === XML_PI_NODE) {
                        $domResult->removeChild($node);
                    }
                }

                $html = $domResult->saveHTML();
                // convert CJK entities and double encdoded quotes back - otherwise CJK characters in title attribute are broken
                $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
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
