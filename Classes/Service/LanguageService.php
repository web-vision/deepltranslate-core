<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use TYPO3\CMS\Core\Site\Entity\Site;
use WebVision\Deepltranslate\Core\Exception\InvalidArgumentException;
use WebVision\Deepltranslate\Core\Exception\LanguageRecordNotFoundException;

final class LanguageService
{
    protected DeeplService $deeplService;

    public function __construct(
        DeeplService $deeplService
    ) {
        $this->deeplService = $deeplService;
    }

    /**
     * @return array{uid: int, title: string, language_isocode: string, languageCode: string}
     */
    public function getSourceLanguage(Site $currentSite): array
    {
        $languageIsoCode = $currentSite->getDefaultLanguage()->getLocale()->getLanguageCode();
        $sourceLanguageRecord = [
            'uid' => $currentSite->getDefaultLanguage()->getLanguageId(),
            'title' => $currentSite->getDefaultLanguage()->getTitle(),
            'language_isocode' => strtoupper($languageIsoCode),
            'languageCode' => strtoupper($languageIsoCode),
        ];

        if (!$this->deeplService->isSourceLanguageSupported($sourceLanguageRecord['language_isocode'])) {
            // When sources language not supported oder not exist set auto detect for deepL API
            $sourceLanguageRecord['title'] = 'auto';
            $sourceLanguageRecord['language_isocode'] = 'auto';
            $sourceLanguageRecord['languageCode'] = 'auto';
        }

        return $sourceLanguageRecord;
    }

    /**
     * @return array{uid: int, title: string, language_isocode: string, languageCode: string, formality: string}
     * @throws LanguageRecordNotFoundException
     * @throws InvalidArgumentException
     */
    public function getTargetLanguage(Site $currentSite, int $languageId): array
    {
        try {
            $language = $currentSite->getLanguageById($languageId);
        } catch (\Exception $e) {
            if ($e->getCode() === 1522960188) {
                throw new LanguageRecordNotFoundException(
                    sprintf('Language "%d" in site "%s" not found.', $languageId, $currentSite->getIdentifier()),
                    1746959505,
                    $e,
                );
            }
            throw $e;
        }
        $configuration = $language->toArray();
        $deeplTargetLanguage = $configuration['deeplTargetLanguage'] ?? null;
        if ($deeplTargetLanguage === null || $deeplTargetLanguage === '') {
            throw new InvalidArgumentException(
                sprintf('Missing deeplTargetLanguage or Language "%d" in site "%s"', $languageId, $currentSite->getIdentifier()),
                1746973481,
            );
        }

        if (!$this->deeplService->isTargetLanguageSupported($deeplTargetLanguage)) {
            throw new InvalidArgumentException(
                sprintf('The given language key "%s" is not supported by DeepL. Possibly wrong Site configuration.', $deeplTargetLanguage),
                1746959745,
            );
        }

        return [
            'uid' => $language->getLanguageId(),
            'title' => $language->getTitle(),
            'language_isocode' => $deeplTargetLanguage,
            'languageCode' => $deeplTargetLanguage,
            'formality' => $configuration['deeplFormality'] ?? '',
        ];
    }
}
