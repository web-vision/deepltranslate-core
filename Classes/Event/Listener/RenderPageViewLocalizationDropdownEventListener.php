<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Event\Listener;

use TYPO3\CMS\Backend\View\PageLayoutContext;
use WebVision\Deepl\Base\Event\ViewHelpers\ModifyInjectVariablesViewHelperEvent;
use WebVision\Deepltranslate\Core\Utility\DeeplBackendUtility;

final class RenderPageViewLocalizationDropdownEventListener
{
    public function __invoke(ModifyInjectVariablesViewHelperEvent $event): void
    {
        if ($event->getIdentifier() !== 'languageTranslationDropdown') {
            return;
        }
        $translationPartials = $event->getLocalVariableProvider()->get('translationPartials');
        if ($translationPartials === null) {
            $translationPartials = [];
        }
        $translationPartials[20] = 'Translation/DeeplTranslationDropdown';
        $event->getLocalVariableProvider()->add('translationPartials', $translationPartials);

        $deeplTranslateLanguages = [];
        $event->getLocalVariableProvider()->add('deeplLanguages', []);
        /** @var PageLayoutContext|null $context */
        $context = $event->getGlobalVariableProvider()->get('context');
        if ($context === null) {
            return;
        }

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
                $deeplTranslateLanguages[$siteLanguage->getTitle()] = $siteLanguage->getLanguageId();
            }
        }
        if ($deeplTranslateLanguages === []) {
            return;
        }
        $options = [];
        foreach ($context->getNewLanguageOptions() as $key => $possibleLanguage) {
            if ($key === 0) {
                continue;
            }
            if (!array_key_exists($possibleLanguage, $deeplTranslateLanguages)) {
                continue;
            }
            $parameters = [
                'justLocalized' => 'pages:' . $context->getPageId() . ':' . $deeplTranslateLanguages[$possibleLanguage],
                'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
            ];

            $redirectUrl = DeeplBackendUtility::buildBackendRoute('record_edit', $parameters);
            $params = [];
            $params['redirect'] = $redirectUrl;
            $params['cmd']['pages'][$context->getPageId()]['deepltranslate'] = $deeplTranslateLanguages[$possibleLanguage];

            $targetUrl = DeeplBackendUtility::buildBackendRoute('tce_db', $params);

            $options[$targetUrl] = $possibleLanguage;
        }

        if ($options === []) {
            return;
        }
        $event->getLocalVariableProvider()->add('deeplLanguages', $options);
    }
}
