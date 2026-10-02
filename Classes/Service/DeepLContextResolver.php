<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Resolves the context sent to DeepL with the translation of a record, see
 * https://developers.deepl.com/docs/best-practices/working-with-context.
 *
 * The context of the page the record is on (`tx_wvdeepltranslate_context`, in the version of the current workspace)
 * replaces the context of its site (`deeplContext` of the site configuration). A context of whitespace only counts
 * as none.
 *
 * @internal
 */
final class DeepLContextResolver
{
    public function __construct(
        private readonly SiteFinder $siteFinder,
    ) {}

    /**
     * @param int $pageId the page of the translated record, the page itself for a page
     * @return string the context, an empty string for none
     */
    public function resolve(int $pageId): string
    {
        if ($pageId <= 0) {
            return '';
        }
        $page = BackendUtility::getRecordWSOL('pages', $pageId, 'tx_wvdeepltranslate_context');
        $pageContext = trim((string)($page['tx_wvdeepltranslate_context'] ?? ''));
        if ($pageContext !== '') {
            return $pageContext;
        }
        try {
            $siteContext = $this->siteFinder->getSiteByPageId($pageId)->getConfiguration()['deeplContext'] ?? '';
        } catch (SiteNotFoundException) {
            return '';
        }
        return is_string($siteContext) ? trim($siteContext) : '';
    }
}
