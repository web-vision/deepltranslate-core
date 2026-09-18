<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Core13\Form;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use WebVision\Deepltranslate\Core\Form\InlineLocalizeWithDeeplControlRendererInterface;

/**
 * TYPO3 v13 implementation of {@see InlineLocalizeWithDeeplControlRendererInterface}: a button dispatching
 * the `deepltranslate` DataHandler command through the core `tce_db` route, behind the core confirmation
 * dialog (`t3js-modal-trigger`), which warns that unsaved changes of the form are discarded.
 *
 * A button and not a link, like the other controls of the inline record, so the command cannot be opened
 * in a new tab without the confirmation.
 *
 * @internal No public API
 */
#[AsAlias(id: InlineLocalizeWithDeeplControlRendererInterface::class)]
final readonly class InlineLocalizeWithDeeplControlRenderer implements InlineLocalizeWithDeeplControlRendererInterface
{
    private const LABEL_PREFIX = 'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:';

    public function __construct(
        private UriBuilder $uriBuilder,
        private IconFactory $iconFactory,
    ) {}

    public function render(array $data, string $table, int $uid, int $targetLanguageId): string
    {
        // The URL of the edit form currently open, path absolute and without the preview parameters:
        // `R_URI` of the core `EditDocumentController`, handed down to inline children and to their AJAX
        // responses. Without it, the editor would land on an empty page after the translation.
        $returnUrl = (string)($data['returnUrl'] ?? '');
        if ($returnUrl === '') {
            return '';
        }
        $languageService = $this->getLanguageService();
        $label = $languageService->sL(self::LABEL_PREFIX . 'inline.button.translateWithDeepl');

        return sprintf(
            '<button type="button" class="btn btn-default t3js-modal-trigger t3js-deepltranslate-inline-localize"'
            . ' data-uri="%s" title="%s" data-title="%s" data-content="%s" data-severity="warning"'
            . ' data-button-ok-text="%s" data-button-close-text="%s">%s</button>',
            htmlspecialchars($this->buildLocalizeUrl($table, $uid, $targetLanguageId, $returnUrl)),
            htmlspecialchars($label),
            htmlspecialchars($label),
            htmlspecialchars($languageService->sL(self::LABEL_PREFIX . 'inline.confirmation.content')),
            htmlspecialchars($label),
            htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:cancel')),
            $this->iconFactory->getIcon('actions-localize-deepl-13', IconSize::SMALL)->render()
        );
    }

    private function buildLocalizeUrl(string $table, int $uid, int $targetLanguageId, string $returnUrl): string
    {
        return (string)$this->uriBuilder->buildUriFromRoute('tce_db', [
            'cmd' => [
                $table => [
                    $uid => [
                        'deepltranslate' => $targetLanguageId,
                    ],
                ],
            ],
            'redirect' => $returnUrl,
        ]);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
