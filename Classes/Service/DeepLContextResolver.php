<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Resolves the translation context used to enrich a DeepL translation request.
 *
 * A context explicitly configured on a page (within the "DeepL Translate" tab of
 * the page properties) takes precedence over the site-wide configured context.
 * An empty page value falls back to the site context, an empty site context
 * disables the context for the translation request.
 */
final class DeepLContextResolver
{
    /**
     * @param int $pageId The page uid the translated record belongs to (0 if unknown).
     * @param string $siteContext The site-wide configured context (may be empty).
     */
    public function resolve(int $pageId, string $siteContext): string
    {
        if ($pageId <= 0) {
            return $siteContext;
        }

        $pageRecord = BackendUtility::getRecord('pages', $pageId);
        $pageContext = (string)($pageRecord['tx_wvdeepltranslate_context'] ?? '');

        return $pageContext !== '' ? $pageContext : $siteContext;
    }
}