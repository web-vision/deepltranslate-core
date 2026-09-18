<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use WebVision\Deepltranslate\Core\Access\AllowedTranslateAccess;
use WebVision\Deepltranslate\Core\Event\DisallowTableFromDeeplTranslateEvent;
use WebVision\Deepltranslate\Core\Utility\DeeplBackendUtility;

/**
 * Decides whether a DeepL translate action may be offered for a record, applying the same rules as the
 * DeepL buttons of the list module: an API key is configured, the table is not excluded through
 * {@see DisallowTableFromDeeplTranslateEvent}, the backend user holds the DeepL translate permission and
 * access to the target language, and the site configures DeepL for the source and target language.
 *
 * @internal No public API
 */
final readonly class DeeplTranslateAvailabilityService
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function isAvailableForRecord(
        BackendUserAuthentication $backendUser,
        string $table,
        int $pageId,
        int $targetLanguageId,
    ): bool {
        return DeeplBackendUtility::isDeeplApiKeySet()
            && $this->isTableAllowed($table)
            && ($backendUser->isAdmin() || $backendUser->check('custom_options', AllowedTranslateAccess::ALLOWED_TRANSLATE_OPTION_VALUE))
            && $backendUser->checkLanguageAccess($targetLanguageId)
            && DeeplBackendUtility::checkCanBeTranslated($pageId, $targetLanguageId);
    }

    private function isTableAllowed(string $table): bool
    {
        /** @var DisallowTableFromDeeplTranslateEvent $event */
        $event = $this->eventDispatcher->dispatch(new DisallowTableFromDeeplTranslateEvent(
            tableName: $table,
            translateButtonsAllowed: true,
        ));

        return $event->isTranslateButtonsAllowed();
    }
}
