<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\ViewHelpers\Be\Access;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;
use WebVision\Deepltranslate\Core\Access\AllowedTranslateAccess;
use WebVision\Deepltranslate\Core\ConfigurationInterface;

/**
 * @internal This ViewHelper is marked internal and only to be used within
 * the DeepL translate packages and therefore no public API.
 */
final class DeeplTranslateAllowedViewHelper extends AbstractConditionViewHelper
{
    public static function verdict(array $arguments, RenderingContextInterface $renderingContext): bool
    {
        $deeplConfiguration = GeneralUtility::makeInstance(ConfigurationInterface::class);
        if ($deeplConfiguration->getApiKey() === '') {
            return false;
        }
        /** @var RenderingContext $renderingContext */
        $currentPageId = (int)($renderingContext->getRequest()?->getQueryParams()['id'] ?? 0);
        // set default to true avoiding breaking behaviour issues
        if (!$deeplConfiguration->isDeeplTranslationAllowedOnPage($currentPageId)) {
            return false;
        }
        if (self::getBackendUserAuthentication()->check('custom_options', AllowedTranslateAccess::ALLOWED_TRANSLATE_OPTION_VALUE)) {
            return true;
        }

        return false;
    }

    protected static function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
