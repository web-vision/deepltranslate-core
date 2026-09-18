<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Core13\Form;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\MathUtility;
use WebVision\Deepltranslate\Core\Form\InlineLocalizeWithDeeplControlRendererInterface;

/**
 * TYPO3 v13 implementation of {@see InlineLocalizeWithDeeplControlRendererInterface}: a link dispatching
 * the `deepltranslate` DataHandler command through the core `tce_db` route, behind the core confirmation
 * dialog (`t3js-modal-trigger`), which warns that unsaved changes of the form are discarded.
 *
 * @internal No public API
 */
#[AsAlias(id: InlineLocalizeWithDeeplControlRendererInterface::class)]
final readonly class InlineLocalizeWithDeeplControlRenderer implements InlineLocalizeWithDeeplControlRendererInterface
{
    public function __construct(
        private UriBuilder $uriBuilder,
        private IconFactory $iconFactory,
    ) {}

    public function render(array $data, string $table, int $uid, int $targetLanguageId): string
    {
        $languageService = $this->getLanguageService();

        return sprintf(
            '<a href="%s" class="btn btn-default t3js-modal-trigger t3js-deepltranslate-inline-localize" title="%s"'
            . ' data-title="%s" data-content="%s" data-severity="warning" data-button-ok-text="%s"'
            . ' data-button-close-text="%s">%s</a>',
            htmlspecialchars($this->buildLocalizeUrl($data, $table, $uid, $targetLanguageId)),
            htmlspecialchars($languageService->sL('LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:inline.button.localizeWithDeepl')),
            htmlspecialchars($languageService->sL('LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:inline.confirmation.title')),
            htmlspecialchars($languageService->sL('LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:inline.confirmation.content')),
            htmlspecialchars($languageService->sL('LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:inline.confirmation.ok')),
            htmlspecialchars($languageService->sL('LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:inline.confirmation.cancel')),
            $this->iconFactory->getIcon('actions-localize-deepl-13', IconSize::SMALL)->render()
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildLocalizeUrl(array $data, string $table, int $uid, int $targetLanguageId): string
    {
        return (string)$this->uriBuilder->buildUriFromRoute('tce_db', [
            'cmd' => [
                $table => [
                    $uid => [
                        'deepltranslate' => $targetLanguageId,
                    ],
                ],
            ],
            'redirect' => $this->buildRedirectUrl($data),
        ]);
    }

    /**
     * The document currently open when the control is rendered with the edit form - a saved form is always
     * redirected to its GET URL - otherwise, for example in an AJAX response, the edit form of the top-most
     * record containing the inline field.
     *
     * @param array<string, mixed> $data
     */
    private function buildRedirectUrl(array $data): string
    {
        $request = $data['request'] ?? null;
        $route = $request instanceof ServerRequestInterface ? $request->getAttribute('route') : null;
        if ($request instanceof ServerRequestInterface && $route instanceof Route && $route->getOption('_identifier') === 'record_edit') {
            return (string)$request->getUri();
        }
        $table = (string)($data['inlineTopMostParentTableName'] ?? '');
        $uid = $data['inlineTopMostParentUid'] ?? '';
        if ($table === '' || !MathUtility::canBeInterpretedAsInteger($uid)) {
            $table = (string)($data['inlineParentTableName'] ?? '');
            $uid = $data['inlineParentUid'] ?? '';
        }

        return (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [
                $table => [
                    (int)$uid => 'edit',
                ],
            ],
            'returnUrl' => (string)($data['returnUrl'] ?? ''),
        ], UriBuilder::ABSOLUTE_URL);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
