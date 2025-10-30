<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;

/**
 * @internal not part of public deepl extension api
 */
#[Autoconfigure(public: true)]
final class IconOverlayGenerator
{
    private IconFactory $iconFactory;

    public function __construct(
        IconFactory $iconFactory
    ) {
        $this->iconFactory = $iconFactory;
    }

    /**
     * Get overlay icon
     */
    public function get(string $baseIdentifier, string $deeplIdentifier = 'deepl-grey-logo', string $size = Icon::SIZE_SMALL): Icon
    {
        return $this->iconFactory->getIcon($baseIdentifier, $size, $deeplIdentifier);
    }
}
