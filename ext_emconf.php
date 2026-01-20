<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'DeepL Translate',
    'description' => 'This extension provides option to translate content element, and TCA record texts to DeepL supported languages.',
    'category' => 'backend',
    'author' => 'web-vision GmbH Team',
    'author_company' => 'web-vision GmbH',
    'author_email' => 'hello@web-vision.de',
    'state' => 'stable',
    'version' => '5.1.2',
    'constraints' => [
        'depends' => [
            'php' => '8.1.0-8.4.99',
            'typo3' => '12.4.0-13.4.99',
            'backend' => '12.4.0-13.4.99',
            'extbase' => '12.4.0-13.4.99',
            'fluid' => '12.4.0-13.4.99',
            'setup' => '12.4.0-13.4.99',
            'deepl_base' => '1.0.2-1.99.99',
            'deeplcom_deeplphp' => '1.16.0-1.99.99',
        ],
        'conflicts' => [
            'recordlist_thumbnail' => '*',
            'wv_deepltranslate' => '*',
        ],
        'suggests' => [
            'container' => '*',
            'dashboard' => '*',
            'install' => '*',
            'enable_translated_content' => '*',
            'deepltranslate_assets' => '*',
            'deepltranslate_glossary' => '*',
            'gridelements' => '*',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'WebVision\\Deepltranslate\\Core\\' => 'Classes',
        ],
    ],
];
