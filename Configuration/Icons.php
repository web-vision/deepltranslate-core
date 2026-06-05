<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use WebVision\Deepl\Base\Imaging\IconProvider\DeeplBaseSvgIconProvider;

return [
    'actions-localize-deepl-13' => [
        'provider' => DeeplBaseSvgIconProvider::class,
        'source' => 'EXT:deepltranslate_core/Resources/Public/Icons/deepl-mode-aware.svg',
    ],
    'actions-localize-deepl-14' => [
        'provider' => DeeplBaseSvgIconProvider::class,
        'source' => 'EXT:deepltranslate_core/Resources/Public/Icons/deepl-mode-aware.svg',
    ],
    'deepl-grey-logo' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:deepltranslate_core/Resources/Public/Icons/deepl-grey.svg',
    ],
    'deepl-logo' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:deepltranslate_core/Resources/Public/Icons/deepl.svg',
    ],
    'deepl-logo-mode-aware' => [
        'provider' => DeeplBaseSvgIconProvider::class,
        'source' => 'EXT:deepltranslate_core/Resources/Public/Icons/deepl-mode-aware.svg',
    ],
];
