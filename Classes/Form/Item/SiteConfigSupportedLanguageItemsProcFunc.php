<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Form\Item;

use DeepL\Language;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use WebVision\Deepltranslate\Core\Service\DeeplService;

/**
 * Adds DeepL related fields to the Site configuration
 */
#[Autoconfigure(public: true)]
final class SiteConfigSupportedLanguageItemsProcFunc
{
    private DeeplService $deeplService;

    public function __construct(
        DeeplService $deeplService
    ) {
        $this->deeplService = $deeplService;
    }

    public function getSupportedLanguageForField(array &$configuration): void
    {
        $supportedLanguages = $this->deeplService->getSupportLanguage()['target'];

        $configuration['items'][] = ['--- Select a Language ---', null];
        /** @var Language $supportedLanguage */
        foreach ($supportedLanguages as $supportedLanguage) {
            $configuration['items'][] = [
                'label' => $supportedLanguage->name,
                'value' => $supportedLanguage->code,
            ];
        }
    }
}
