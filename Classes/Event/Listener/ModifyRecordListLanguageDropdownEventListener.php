<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Event\Listener;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepl\Base\Event\ViewHelpers\ModifyInjectVariablesViewHelperEvent;
use WebVision\Deepltranslate\Core\ConfigurationInterface;
use WebVision\Deepltranslate\Core\Form\TranslationDropdownGenerator;

/**
 * This listener helps to modify the Recordlist language dropdown
 * and adds the possibility removing the default dropdown by page TS setting
 */
final class ModifyRecordListLanguageDropdownEventListener
{
    public function __construct(
        private readonly SiteFinder $siteFinder,
        private readonly TranslationDropdownGenerator $generator,
        private readonly ConfigurationInterface $configuration,
    ) {
    }

    public function __invoke(ModifyInjectVariablesViewHelperEvent $event): void
    {
        if ($event->getIdentifier() !== 'core-template-recordlist') {
            return;
        }

        $coreLanguageSelectorHtml = $event->getGlobalVariableProvider()->get('languageSelectorHtml');
        // means, no translations available, we can abort here.
        if ($coreLanguageSelectorHtml === '') {
            return;
        }
        $currentPageId = $event->getGlobalVariableProvider()->get('pageId');
        if (!$this->configuration->isDeeplTranslateAllowed($currentPageId)) {
            return;
        }
        if ($this->configuration->isCoreTranslationDisabled($currentPageId)) {
            $coreLanguageSelectorHtml = '';
        } else {
            $coreLanguageSelectorHtml = str_replace('<div class="form-row">', '', $coreLanguageSelectorHtml);
            $coreLanguageSelectorHtml = substr($coreLanguageSelectorHtml, 0, (strlen($coreLanguageSelectorHtml) - strlen('</div>')));
        }

        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;

        $deeplLanguageSelectorHtml = '';
        $site = $this->siteFinder->getSiteByPageId($currentPageId);
        $siteLanguages = $site->getLanguages();
        $options = $this->generator->buildTranslateDropdownOptions($siteLanguages, $currentPageId, $request->getUri());
        if ($options !== '') {
            $deeplLanguageSelectorHtml = '<div class="form-group">'
                . '<div class="row">'
                . '<label class="col-md-4 control-label" for="deeplLanguageTranslation">'
                . $this->getLanguageService()->sL('LLL:EXT:deepltranslate_core/Resources/')
                . '</label>'
                . '<select class="form-select" id="deeplLanguageTranslation" name="createNewLanguage" data-global-event="change" data-action-navigate="$value">'
                . $options
                . '</select>'
                . '</div>'
                . '</div>';
        }

        $event->getGlobalVariableProvider()->add(
            'languageSelectorHtml',
            sprintf('<div class="form-row">%s %s</div>', $coreLanguageSelectorHtml, $deeplLanguageSelectorHtml)
        );
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'] ?? GeneralUtility::makeInstance(LanguageServiceFactory::class)
            ->createFromUserPreferences($GLOBALS['USER'] ?? null);
    }
}
