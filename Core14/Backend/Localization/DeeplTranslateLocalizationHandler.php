<?php

namespace WebVision\Deepltranslate\Core\Core14\Backend\Localization;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository;
use TYPO3\CMS\Backend\Localization\Finisher\NoopLocalizationFinisher;
use TYPO3\CMS\Backend\Localization\Finisher\RedirectLocalizationFinisher;
use TYPO3\CMS\Backend\Localization\Finisher\ReloadLocalizationFinisher;
use TYPO3\CMS\Backend\Localization\LocalizationHandlerInterface;
use TYPO3\CMS\Backend\Localization\LocalizationInstructions;
use TYPO3\CMS\Backend\Localization\LocalizationMode;
use TYPO3\CMS\Backend\Localization\LocalizationResult;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Access\AllowedTranslateAccess;
use WebVision\Deepltranslate\Core\Event\DisallowTableFromDeeplTranslateEvent;
use WebVision\Deepltranslate\Core\Exception\InvalidArgumentException as DeeplTranslateCoreInvalidArgumentException;
use WebVision\Deepltranslate\Core\Exception\LanguageRecordNotFoundException;
use WebVision\Deepltranslate\Core\Service\LanguageService as DeeplTranslateCoreLanguageService;
use WebVision\Deepltranslate\Core\Utility\DeeplBackendUtility;

/**
 * Handles DeepL based localization through the wizard interface,
 * supporting only translate operations for the translate mode.
 *
 * @internal and not part of public API.
 */
final readonly class DeeplTranslateLocalizationHandler implements LocalizationHandlerInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private UriBuilder $uriBuilder,
        private LocalizationRepository $localizationRepository,
        private SiteFinder $siteFinder,
        private DeeplTranslateCoreLanguageService $deeplTranslateCoreLanguageService,
    ) {}

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return 'deepltranslate';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return 'deepltranslate_core.wizards.localization:handler.deepltranslate.label';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'deepltranslate_core.wizards.localization:handler.deepltranslate.description';
    }

    /**
     * @inheritDoc
     */
    public function getIconIdentifier(): string
    {
        return sprintf('actions-localize-deepl-%s', ((new Typo3Version())->getMajorVersion()));
    }

    public function isAvailable(LocalizationInstructions $instructions): bool
    {
        if ($instructions->mode !== LocalizationMode::TRANSLATE) {
            // Early return for invalid localization mode
            return false;
        }
        if (DeeplBackendUtility::isDeeplApiKeySet() === false) {
            // No API key set, skip DeepL translation handler.
            return false;
        }
        if ($this->isDeeplTranslateAllowedForUser() === false) {
            // DeepL translate not allowed for current user.
            return false;
        }
        /** @var DisallowTableFromDeeplTranslateEvent $event */
        $event = $this->eventDispatcher->dispatch(new DisallowTableFromDeeplTranslateEvent(
            tableName: $instructions->mainRecordType,
            translateButtonsAllowed: true,
        ));
        if ($event->isTranslateButtonsAllowed() === false) {
            // DeepL based translat disallowed for main record type (table).
            return false;
        }
        $determinedSiteConfiguration = $this->determineSiteConfigAndSiteLanguageForLocalizationInstructions($instructions);
        $site = $determinedSiteConfiguration['site'] ?? null;
        $sourceSiteLanguage = $determinedSiteConfiguration['sourceLanguage'];
        $targetSiteLanguage = $determinedSiteConfiguration['targetLanguage'];
        if ($this->isDeeplTranslateAllowedForSite($site, $sourceSiteLanguage, $targetSiteLanguage) === false) {
            // Site configuration and language could not be determined and
            // thus not having a valid DeepL configuration.
            return false;
        }
        return true;
    }

    public function processLocalization(LocalizationInstructions $instructions): LocalizationResult
    {
        return match ($instructions->mainRecordType) {
            // Handle pages with optional content selection
            'pages' => $this->processPageLocalization($instructions->mode, $instructions->recordUid, $instructions->targetLanguageId, $instructions->additionalData),
            // Handle single record localization for other record types
            default => $this->processSingleRecordLocalization($instructions->mode, $instructions->mainRecordType, $instructions->recordUid, $instructions->targetLanguageId),
        };
    }

    /**
     * Process single record localization (non-page records)
     */
    private function processSingleRecordLocalization(
        LocalizationMode $mode,
        string $type,
        int $uid,
        int $targetLanguage
    ): LocalizationResult {
        // Validate that the record exists
        $record = BackendUtility::getRecord($type, $uid);
        if (!$record) {
            return LocalizationResult::error(
                [
                    sprintf(
                        $this->getLanguageService()->sL('deepltranslate_core.wizards.localization:error.recordNotFound'),
                        $uid,
                        $type
                    ),
                ]
            );
        }

        // Check if translation already exists
        $existingTranslation = $this->localizationRepository->getRecordTranslation($type, $uid, $targetLanguage);
        if ($existingTranslation !== null) {
            // Translation already exists, return success with no-op finisher
            return LocalizationResult::success(
                new NoopLocalizationFinisher()
            );
        }

        $cmd = [
            $type => [
                $uid => [
                    $mode->getDataHandlerCommand() => $targetLanguage,
                ],
            ],
        ];

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        if ($dataHandler->errorLog !== []) {
            return LocalizationResult::error($dataHandler->errorLog);
        }

        // Get the newly created record UID from DataHandler's copy mapping
        $newUid = $dataHandler->copyMappingArray_merged[$type][$uid] ?? null;

        // If no UID was found in copy mapping, try to find the translated record
        if ($newUid === null) {
            $translation = $this->localizationRepository->getRecordTranslation($type, $uid, $targetLanguage);
            $newUid = $translation?->getUid();
        }

        // Generate redirect finisher or use reload finisher as fallback
        $redirectUrl = $newUid !== null ? $this->generateRedirectUrl($type, $newUid, $targetLanguage) : null;

        return LocalizationResult::success(
            $redirectUrl !== null
                ? new RedirectLocalizationFinisher($redirectUrl)
                : new ReloadLocalizationFinisher()
        );
    }

    /**
     * Process page localization including selected content elements
     *
     * @param array{selectedRecordUids?: int[]} $additionalData
     */
    private function processPageLocalization(
        LocalizationMode $mode,
        int $pageUid,
        int $targetLanguage,
        array $additionalData
    ): LocalizationResult {
        // Get selected content elements from additionalData
        $selectedContent = $additionalData['selectedRecordUids'] ?? [];

        $cmd = [];

        // Step 1: Check if page translation already exists
        $pageTranslation = $this->localizationRepository->getPageTranslations($pageUid, [$targetLanguage], $this->getBackendUser()->workspace);
        if ($pageTranslation === []) {
            // Page translation doesn't exist - create it
            // Always use 'localize' command for pages (even for copy mode)
            // as we need to create a proper page translation/overlay
            $cmd['pages'] = [
                $pageUid => [
                    'deepltranslate' => $targetLanguage,
                ],
            ];
        }

        // Step 2: Add selected content elements to the command
        if (!empty($selectedContent)) {
            $cmd['tt_content'] = [];
            foreach ($selectedContent as $contentUid) {
                $cmd['tt_content'][(int)$contentUid] = [
                    'deepltranslate' => $targetLanguage,
                ];
            }
        }

        // If no commands were built (page already exists, no content selected),
        // still return success with a no-op finisher
        if (empty($cmd)) {
            // Use no-op finisher to indicate nothing was done, but offer to reload

            return LocalizationResult::success(
                new NoopLocalizationFinisher()
            );
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        if ($dataHandler->errorLog !== []) {
            return LocalizationResult::error($dataHandler->errorLog);
        }

        // Generate redirect finisher to the page layout in the target language or use reload finisher as fallback
        $redirectUrl = $this->generateRedirectUrl('pages', $pageUid, $targetLanguage);
        return LocalizationResult::success(
            $redirectUrl !== null
                ? new RedirectLocalizationFinisher($redirectUrl)
                : new ReloadLocalizationFinisher()
        );
    }

    /**
     * Generate redirect URL based on record type
     */
    private function generateRedirectUrl(string $type, int $uid, int $targetLanguage): ?string
    {
        if ($type === 'pages') {
            // Redirect to page layout module with the target language
            return (string)$this->uriBuilder->buildUriFromRoute('web_layout', [
                'id' => $uid,
                'languages' => [$targetLanguage],
            ]);
        }

        // For other record types, redirect to the edit form of the translated record
        $record = BackendUtility::getRecord($type, $uid);
        if ($record && isset($record['pid'])) {
            $returnUrl = null;

            if ($type === 'sys_file_metadata') {
                // Get the file from the metadata record and build return URL to filelist module
                try {
                    $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject((int)$record['file']);
                    $parentFolder = $file->getParentFolder();
                    $returnUrl = (string)$this->uriBuilder->buildUriFromRoute(
                        'media_management',
                        ['id' => $parentFolder->getCombinedIdentifier()]
                    );
                } catch (\Exception) {
                    // File not found or inaccessible, fall back to default return URL
                }
            }

            if ($returnUrl === null) {
                $returnUrl = (string)$this->uriBuilder->buildUriFromRoute(
                    'web_layout',
                    [
                        'id' => $record['pid'],
                        'languages' => [$targetLanguage],
                    ]
                );
            }

            // Redirect to edit form for the newly created record
            return (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    $type => [
                        $uid => 'edit',
                    ],
                ],
                'returnUrl' => $returnUrl,
            ]);
        }

        return null;
    }

    /**
     * @return array{site: Site|null, sourceLanguage: SiteLanguage|null, targetLanguage: SiteLanguage|null}
     */
    private function determineSiteConfigAndSiteLanguageForLocalizationInstructions(LocalizationInstructions $instructions): array
    {
        if ($instructions->mainRecordType === 'pages') {
            return $this->determineSiteInformation(
                $instructions->recordUid,
                $instructions->sourceLanguageId,
                $instructions->targetLanguageId,
            );
        }
        $record = BackendUtility::getRecord($instructions->mainRecordType, $instructions->recordUid);
        if (!$record) {
            // Could not retrieve record, which makes it impossible to determine and site configuration.
            return [
                'site' => null,
                'sourceLanguage' => null,
                'targetLanguage' => null,
            ];
        }
        $recordPid = (int)($record['pid'] ?? 0);
        if ($recordPid === 0) {
            // @todo How to handle PID=0 records ? Return as invalid for now.
            // @todo This would automatically rule out `sys_file` and `sys_file_metadata` (FAL) for now,
            //       and needs to be implemented to get `EXT:deepltranslate_assets` working for TYPO3v14.
            return [
                'site' => null,
                'sourceLanguage' => null,
                'targetLanguage' => null,
            ];
        }
        return $this->determineSiteInformation(
            $recordPid,
            $instructions->sourceLanguageId,
            $instructions->targetLanguageId,
        );
    }

    /**
     * @param int $pageId
     * @param int $sourceLanguageId
     * @param int $targetLanguageId
     * @return array{site: Site|null, sourceLanguage: SiteLanguage|null, targetLanguage: SiteLanguage|null}
     */
    private function determineSiteInformation(int $pageId, int $sourceLanguageId, int $targetLanguageId): array
    {
        try {
            // Validate that the record exists
            $site = $this->siteFinder->getSiteByPageId($pageId);
            $sourceSiteLanguage = $site->getLanguageById($sourceLanguageId);
            $targetSiteLanguage = $site->getLanguageById($targetLanguageId);
            return [
                'site' => $site,
                'sourceLanguage' => $sourceSiteLanguage,
                'targetLanguage' => $targetSiteLanguage,
            ];
        } catch (SiteNotFoundException|\InvalidArgumentException) {
            return [
                'site' => null,
                'sourceLanguage' => null,
                'targetLanguage' => null,
            ];
        }
    }

    private function isDeeplTranslateAllowedForSite(?Site $site, ?SiteLanguage $sourceLanguage, ?SiteLanguage $targetLanguage): bool
    {
        if ($site === null || $sourceLanguage === null || $targetLanguage === null) {
            return false;
        }
        try {
            $this->deeplTranslateCoreLanguageService->getSourceLanguage($site, $sourceLanguage->getLanguageId());
            $this->deeplTranslateCoreLanguageService->getTargetLanguage($site, $targetLanguage->getLanguageId());
        } catch (LanguageRecordNotFoundException|DeeplTranslateCoreInvalidArgumentException) {
            // Either source or target language is invalid.
            return false;
        }
        return true;
    }

    private function isDeeplTranslateAllowedForUser(): bool
    {
        $user = $this->getBackendUser();
        return $user->isAdmin() || $user->check('custom_options', AllowedTranslateAccess::ALLOWED_TRANSLATE_OPTION_VALUE);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
