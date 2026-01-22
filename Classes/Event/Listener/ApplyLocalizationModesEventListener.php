<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Event\Listener;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Information\Typo3Version;
use WebVision\Deepl\Base\Controller\Backend\LocalizationController;
use WebVision\Deepl\Base\Event\GetLocalizationModesEvent;
use WebVision\Deepl\Base\Localization\LocalizationMode;
use WebVision\Deepltranslate\Core\Access\AllowedTranslateAccess;

/**
 * Provides deepltranslate related localization modes by listening to the PSR-14
 * event {@see GetLocalizationModesEvent} dispatched by extension `deepl_base`
 * in {@see LocalizationController::dispatchGetLocalizationModesEvent()}.
 */
final class ApplyLocalizationModesEventListener
{
    public function __invoke(GetLocalizationModesEvent $event): void
    {
        if (!$this->deeplTranslateAllowed()) {
            // Non-admin user does not have the permission for deepl, return early.
            return;
        }
        $modes = [];
        $majorVersion = (new Typo3Version())->getMajorVersion();
        if ($this->allowDeeplTranslate($event)) {
            $modes[] = new LocalizationMode(
                identifier: 'deepltranslate',
                title: $event->getLanguageService()->sL('LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:localize.educate.deepltranslateHeader'),
                description: $event->getLanguageService()->sL('LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:localize.educate.deepltranslate'),
                icon: ($majorVersion === 13 ? 'actions-localize-deepl-13' : 'actions-localize-deepl'),
                before: [],
                after: [LocalizationController::ACTION_LOCALIZE, LocalizationController::ACTION_COPY],
            );
        }
        if ($this->allowDeeplTranslateAuto($event)) {
            // @todo Consider to drop `deepltranslateauto` mode. Does not make much sense in the current form.
            $modes[] = new LocalizationMode(
                identifier: 'deepltranslateauto',
                title: $event->getLanguageService()->sL('LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:localize.educate.deepltranslateHeaderAutodetect'),
                description: $event->getLanguageService()->sL('LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:localize.educate.deepltranslateAuto'),
                icon: ($majorVersion === 13 ? 'actions-localize-deepl-13' : 'actions-localize-deepl'),
                before: [],
                after: [LocalizationController::ACTION_LOCALIZE, LocalizationController::ACTION_COPY],
            );
        }
        if ($modes !== []) {
            $event->getModes()->add(...array_values($modes));
        }
    }

    private function allowDeeplTranslate(GetLocalizationModesEvent $event): bool
    {
        // @todo Prepared for PageTSConfig feature to toggle `deepltranslate`.
        return true;
    }

    private function allowDeeplTranslateAuto(GetLocalizationModesEvent $event): bool
    {
        // @todo Prepared for PageTSConfig feature to toggle `deepltranslateauto`.
        return true;
    }

    private function deeplTranslateAllowed(): bool
    {
        $user = $this->getBackendUser();
        if ($user !== null) {
            return $user->isAdmin() || $user->check('custom_options', AllowedTranslateAccess::ALLOWED_TRANSLATE_OPTION_VALUE);
        }
        return false;
    }

    private function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
