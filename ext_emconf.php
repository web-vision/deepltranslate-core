<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'DeepL Translate (CORE)',
    'description' => 'This extension provides option to translate content element, and TCA record texts to DeepL supported languages.',
    'version' => '6.0.8',
    'category' => 'backend',
    'state' => 'stable',
    'author' => 'web-vision GmbH Team',
    'author_email' => 'hello@web-vision.de',
    'author_company' => 'web-vision GmbH',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.5.99',
            'typo3' => '13.4.34-14.3.99',
            'backend' => '13.4.34-14.3.99',
            'extbase' => '13.4.34-14.3.99',
            'fluid' => '13.4.34-14.3.99',
            'install' => '13.4.34-14.3.99',
            'deeplcom_deeplphp' => '1.19.0-1.19.99',
            'deepl_base' => '2.0.6-2.99.99',
        ],
        'conflicts' => [
            'recordlist_thumbnail' => '',
            'wv_deepltranslate' => '',
        ],
        'suggests' => [
            'container' => '3.2.3-3.99.99',
            'dashboard' => '',
            'install' => '',
            'enable_translated_content' => '',
            'deepltranslate_assets' => '',
            'deepltranslate_glossary' => '',
            'gridelements' => '',
        ],
    ],
];
