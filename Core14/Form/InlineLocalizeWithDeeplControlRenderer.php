<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Core14\Form;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use WebVision\Deepltranslate\Core\Form\InlineLocalizeWithDeeplControlRendererInterface;

/**
 * TYPO3 v14 implementation of {@see InlineLocalizeWithDeeplControlRendererInterface}, opening the core
 * localization wizard for the inline child. Its script is loaded by every FormEngine document.
 *
 * @internal No public API
 */
#[AsAlias(id: InlineLocalizeWithDeeplControlRendererInterface::class)]
final readonly class InlineLocalizeWithDeeplControlRenderer implements InlineLocalizeWithDeeplControlRendererInterface
{
    public function __construct(
        private IconFactory $iconFactory,
    ) {}

    public function render(array $data, string $table, int $uid, int $targetLanguageId): string
    {
        $title = $this->getLanguageService()->sL(
            'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:inline.button.localizeWithDeepl'
        );

        return sprintf(
            '<typo3-backend-localization-button class="btn btn-default t3js-deepltranslate-inline-localize"'
            . ' record-type="%s" record-uid="%d" target-language="%d" title="%s">%s</typo3-backend-localization-button>',
            htmlspecialchars($table),
            $uid,
            $targetLanguageId,
            htmlspecialchars($title),
            $this->iconFactory->getIcon('actions-localize-deepl-14', IconSize::SMALL)->render()
        );
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
