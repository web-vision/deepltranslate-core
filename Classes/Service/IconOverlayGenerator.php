<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;

/**
 * @internal not part of public deepl extension api
 */
#[Autoconfigure(public: true)]
final class IconOverlayGenerator
{
    public function __construct(
        private IconFactory $iconFactory,
    ) {}

    /**
     * Get overlay icon
     */
    public function get(string $baseIdentifier, string $deeplIdentifier = 'deepl-grey-logo', IconSize $size = IconSize::SMALL): Icon
    {
        return $this->iconFactory->getIcon($baseIdentifier, $size, $deeplIdentifier);
    }
}
