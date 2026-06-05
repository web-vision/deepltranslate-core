<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use WebVision\Deepl\Base\Imaging\IconProvider\DeeplBaseSvgIconProvider;

$majorVersion = (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion();
return [
    'actions-localize-deepl' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:deepltranslate_core/Resources/Public/Icons/actions-localize-deepl.svg',
    ],
    'actions-localize-deepl-12' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:deepltranslate_core/Resources/Public/Icons/actions-localize-deepl.svg',
    ],
    'actions-localize-deepl-13' => [
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
    'deepl-logo-mode-aware' => ($majorVersion === 12)
        ? [
            'provider' => SvgIconProvider::class,
            'source' => 'EXT:deepltranslate_core/Resources/Public/Icons/deepl.svg',
        ]
        : [
            'provider' => DeeplBaseSvgIconProvider::class,
            'source' => 'EXT:deepltranslate_core/Resources/Public/Icons/deepl-mode-aware.svg',
        ],
];
