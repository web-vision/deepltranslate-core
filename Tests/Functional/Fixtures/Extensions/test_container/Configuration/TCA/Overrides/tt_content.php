<?php

use B13\Container\Tca\ContainerConfiguration;
use B13\Container\Tca\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

(static function (): void {
    GeneralUtility::makeInstance(Registry::class)->configureContainer(
        (new ContainerConfiguration(
            'container-50-50',
            '50-50 Container',
            '50-50 Container for testing purposes',
            [
                [
                    [
                        'name' => '50',
                        'colPos' => 100,
                    ],
                    [
                        'name' => '50',
                        'colPos' => 101,
                    ],
                ],
            ]
        ))
        ->setIcon('content-container-columns-2')
        ->setSaveAndCloseInNewContentElementWizard(false)
    );
})();
