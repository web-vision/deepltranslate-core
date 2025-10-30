<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Form\User;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Form\FormDataProvider\EvaluateDisplayConditions;
use WebVision\Deepltranslate\Core\Service\DeeplService;

/**
 * Checks a language having support for formality or not.
 */
#[Autoconfigure(public: true)]
final class HasFormalitySupport
{
    private DeeplService $deeplService;

    public function __construct(
        DeeplService $deeplService
    ) {
        $this->deeplService = $deeplService;
    }

    /**
     * @param array{record?: array{deeplTargetLanguage?: array<int, string>|string|null}} $params

     * @return bool
     */
    public function checkFormalitySupport(array $params, EvaluateDisplayConditions $conditions): bool
    {
        if (!isset($params['record'])) {
            return false;
        }

        $record = $params['record'];
        if (!isset($record['deeplTargetLanguage'])) {
            return false;
        }

        if (is_array($record['deeplTargetLanguage'])) {
            $deeplTargetLanguage = array_pop($record['deeplTargetLanguage']);
        } else {
            $deeplTargetLanguage = $record['deeplTargetLanguage'];
        }

        if ($deeplTargetLanguage === null) {
            return false;
        }

        return $this->deeplService->hasLanguageFormalitySupport($deeplTargetLanguage);
    }
}
