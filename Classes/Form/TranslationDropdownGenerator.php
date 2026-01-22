<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Form;

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Utility\DeeplBackendUtility;

/**
 * Generates the dropdown for language selector
 *
 * @internal only for usage inside deepltranslate-core, no public API
 */
final class TranslationDropdownGenerator
{
    /**
     * @param iterable<SiteLanguage> $siteLanguages
     * @throws \Doctrine\DBAL\Exception
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function buildTranslateDropdownOptions(
        $siteLanguages,
        int $id,
        string|UriInterface $requestUri
    ): string {
        if (!$this->deeplTranslateAllowed()) {
            // Non-admin user does not have the permission for deepl, return early.
            return '';
        }
        $availableTranslations = [];
        foreach ($siteLanguages as $siteLanguage) {
            if (
                $siteLanguage->getLanguageId() === 0
                || $siteLanguage->getLanguageId() === -1
            ) {
                continue;
            }
            $availableTranslations[$siteLanguage->getLanguageId()] = $siteLanguage->getTitle();
        }
        // Then, subtract the languages which are already on the page:
        $localizationParentField = $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'];
        $languageField = $GLOBALS['TCA']['pages']['ctrl']['languageField'];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(
                GeneralUtility::makeInstance(
                    WorkspaceRestriction::class,
                    (int)$this->getBackendUser()?->workspace
                )
            );
        $statement = $queryBuilder
            ->select('uid', $languageField)
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $localizationParentField,
                    $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)
                )
            )
            ->executeQuery();
        while ($pageTranslation = $statement->fetchAssociative()) {
            unset($availableTranslations[(int)$pageTranslation[$languageField]]);
        }
        // If any languages are left, make selector:
        if (!empty($availableTranslations)) {
            $output = '';
            foreach ($availableTranslations as $languageUid => $languageTitle) {
                // check if language can be translated with DeepL
                // otherwise continue to next
                if (!DeeplBackendUtility::checkCanBeTranslated($id, $languageUid)) {
                    continue;
                }
                // Build localize command URL to DataHandler (tce_db)
                // which redirects to FormEngine (record_edit)
                // which, when finished editing should return back to the current page (returnUrl)
                $parameters = [
                    'justLocalized' => 'pages:' . $id . ':' . $languageUid,
                    'returnUrl' => (string)$requestUri,
                ];
                $redirectUrl = DeeplBackendUtility::buildBackendRoute('record_edit', $parameters);
                $params = [];
                $params['redirect'] = $redirectUrl;
                $params['cmd']['pages'][$id]['deepltranslate'] = $languageUid;
                $targetUrl = DeeplBackendUtility::buildBackendRoute('tce_db', $params);
                $output .= '<option value="' . htmlspecialchars($targetUrl) . '">' . htmlspecialchars($languageTitle) . '</option>';
            }
            if ($output !== '') {
                $output = sprintf(
                    '<option value="">%s</option>%s',
                    htmlspecialchars($this->getLocalization()->sL('LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:backend.label')),
                    $output
                );
            }

            return $output;
        }
        return '';
    }

    private function getLocalization(): LanguageService
    {
        return GeneralUtility::makeInstance(LanguageServiceFactory::class)
            ->createFromUserPreferences($this->getBackendUser());
    }

    private function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
