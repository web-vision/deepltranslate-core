<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Utility;

use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use WebVision\Deepltranslate\Core\Configuration;
use WebVision\Deepltranslate\Core\Domain\Dto\CurrentPage;
use WebVision\Deepltranslate\Core\Exception\InvalidArgumentException;
use WebVision\Deepltranslate\Core\Exception\LanguageRecordNotFoundException;
use WebVision\Deepltranslate\Core\Service\IconOverlayGenerator;
use WebVision\Deepltranslate\Core\Service\LanguageService;
use WebVision\Deepltranslate\Core\Service\ProcessingInstruction;

/**
 * Utility helper methods for DeepL-translate
 *
 * Main entry point for detecting API key and current working page
 */
final class DeeplBackendUtility
{
    private static string $apiKey = '';

    private static bool $configurationLoaded = false;

    protected static ?CurrentPage $currentPage = null;

    /**
     * @return string
     */
    public static function getApiKey(): string
    {
        if (!self::$configurationLoaded) {
            self::loadConfiguration();
        }
        return self::$apiKey;
    }

    public static function isDeeplApiKeySet(): bool
    {
        if (!self::$configurationLoaded) {
            self::loadConfiguration();
        }

        return (bool)self::$apiKey;
    }

    public static function loadConfiguration(): void
    {
        $configuration = GeneralUtility::makeInstance(Configuration::class);
        self::$apiKey = $configuration->getApiKey();

        self::$configurationLoaded = true;
    }

    /**
     * ToDo: Migrated function to own class object "WebVision\Deepltranslate\Core\Form\TranslationButtonGenerator"
     */
    public static function buildTranslateButton(
        $table,
        $id,
        $lUid_OnPage,
        $returnUrl,
        $languageTitle = '',
        $flagIcon = ''
    ): string {
        $redirectUrl = self::buildBackendRoute(
            'record_edit',
            [
                'justLocalized' => $table . ':' . $id . ':' . $lUid_OnPage,
                'returnUrl' => $returnUrl,
            ]
        );
        $params = [];
        $params['redirect'] = $redirectUrl;
        //$params['cmd'][$table][$id]['localize'] = $lUid_OnPage;
        //$params['cmd']['localization']['custom']['mode'] = 'deepl';
        $params['cmd'][$table][$id]['deepltranslate'] = $lUid_OnPage;
        $href = self::buildBackendRoute('tce_db', $params);
        $title =
            (string)LocalizationUtility::translate(
                'backend.button.translate',
                'DeepltranslateCore',
                [
                    htmlspecialchars($languageTitle),
                ]
            );

        if ($flagIcon) {
            $iconOverlayGenerator = GeneralUtility::makeInstance(IconOverlayGenerator::class);
            $icon = $iconOverlayGenerator->get($flagIcon);
            $lC = $icon->render();
        } else {
            $lC = GeneralUtility::makeInstance(
                IconFactory::class
            )
                ->getIcon(
                    'actions-localize-deepl',
                    Icon::SIZE_SMALL
                )->render();
        }

        return '<a href="' . htmlspecialchars($href) . '"'
            . '" class="btn btn-default t3js-action-localize"'
            . ' title="' . $title . '">'
            . $lC . '</a> ';
    }

    /**
     * @throws RouteNotFoundException
     */
    public static function buildBackendRoute(string $route, array $parameters): string
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute($route, $parameters);
    }

    public static function checkCanBeTranslated(int $pageId, int $languageId): bool
    {
        try {
            /** @var LanguageService $languageService */
            $languageService = GeneralUtility::makeInstance(LanguageService::class);
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId);
            $languageService->getSourceLanguage($site);
            $languageService->getTargetLanguage($site, $languageId);
        } catch (LanguageRecordNotFoundException|SiteNotFoundException|InvalidArgumentException) {
            return false;
        }
        return true;
    }

    public static function detectCurrentPage(ProcessingInstruction $processingInstruction): ?CurrentPage
    {
        $pageId = null;
        if ($processingInstruction->getProcessingTable() === 'pages') {
            $pageId = (int)$processingInstruction->getProcessingId();
        } elseif (
            $processingInstruction->getProcessingTable() !== null
            && strlen($processingInstruction->getProcessingTable()) > 0
            && MathUtility::canBeInterpretedAsInteger($processingInstruction->getProcessingId())
        ) {
            $pageId = self::getPageIdFromRecord(
                (string)$processingInstruction->getProcessingTable(),
                (int)$processingInstruction->getProcessingId()
            );
        }
        if ($pageId !== null && $pageId > 0) {
            $pageRecord = self::getPageRecord($pageId);
            if ($pageRecord !== null) {
                self::$currentPage = new CurrentPage((int)$pageRecord['uid'], (string)$pageRecord['title']);
            }
        }

        return self::$currentPage;
    }

    /**
     * @return array{uid: int, title: string}|null
     */
    private static function getPageRecord(int $id): ?array
    {
        /** @var array{uid: int, title: string}|null $page */
        $page = BackendUtility::getRecord(
            'pages',
            $id,
            'uid, title'
        );
        return $page;
    }

    private static function getPageIdFromRecord(string $table, int $id): int
    {
        $record = BackendUtility::getRecord(
            $table,
            $id,
            'pid'
        );
        return (int)($record['pid'] ?? null);
    }
}
