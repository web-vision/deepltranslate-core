<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\ViewHelpers;

use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use WebVision\Deepl\Base\Event\ViewHelpers\ModifyInjectVariablesViewHelperEvent;
use WebVision\Deepltranslate\Core\Event\Listener\RenderPageViewLocalizationDropdownEventListener;
use WebVision\Deepltranslate\Core\Utility\DeeplBackendUtility;

/**
 * @deprecated Will be removed in the next major release
 *
 * This viewHelper is deprecated, as the registration is now done by the deepl_base
 * event and directly registered during the EventListener invocation.
 * @see RenderPageViewLocalizationDropdownEventListener
 * @see ModifyInjectVariablesViewHelperEvent
 */
final class DeeplTranslateViewHelper extends AbstractViewHelper
{

    public function initializeArguments(): void
    {
        $this->registerArgument(
            'context',
            PageLayoutContext::class,
            'Layout context',
            true
        );
    }

    /**
     * @return array<int|string, mixed>
     * @throws RouteNotFoundException
     */
    public function render(): array
    {
        trigger_error(
            'This ViewHelper is deprecated and should no longer be used',
            E_USER_DEPRECATED
        );
        $options = [];
        /** @var PageLayoutContext $context */
        $context = $this->arguments['context'];
        $mode = $context->getPageRecord()['module'];

        $languageMatch = [];

        foreach ($context->getSiteLanguages() as $siteLanguage) {
            if (
                $siteLanguage->getLanguageId() != -1
                && $siteLanguage->getLanguageId() != 0
            ) {
                if (!DeeplBackendUtility::checkCanBeTranslated(
                    $context->getPageId(),
                    $siteLanguage->getLanguageId()
                )
                ) {
                    continue;
                }
                $languageMatch[$siteLanguage->getTitle()] = $siteLanguage->getLanguageId();
            }
        }

        if (count($languageMatch) === 0) {
            return $options;
        }
        foreach ($context->getNewLanguageOptions() as $key => $possibleLanguage) {
            if ($key === 0) {
                continue;
            }
            if (!array_key_exists($possibleLanguage, $languageMatch)) {
                continue;
            }
            $parameters = [
                'justLocalized' => 'pages:' . $context->getPageId() . ':' . $languageMatch[$possibleLanguage],
                'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
            ];

            $redirectUrl = DeeplBackendUtility::buildBackendRoute('record_edit', $parameters);
            $params = [];
            $params['redirect'] = $redirectUrl;
            $params['cmd']['pages'][$context->getPageId()]['deepltranslate'] = $languageMatch[$possibleLanguage];

            $targetUrl = DeeplBackendUtility::buildBackendRoute('tce_db', $params);

            $options[$targetUrl] = $possibleLanguage;
        }

        return $options;
    }
}
