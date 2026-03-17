<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Boilerplate for a functional test phpunit boostrap file.
 *
 * This file is loosely maintained within TYPO3 testing-framework, extensions
 * are encouraged to not use it directly, but to copy it to an own place,
 * usually in parallel to a FunctionalTests.xml file.
 *
 * This file is defined in FunctionalTests.xml and called by phpunit
 * before instantiating the test suites.
 */
(static function () {

    /**
     * @todo Fix testing-framework extension package information loading within the framework and remove workaround
     *       here after upgrade to testing-framework release containing the fix.
     */
    $frameworkExtension = [
        'Resources/Core/Functional/Extensions/json_response',
        'Resources/Core/Functional/Extensions/private_container',
    ];
    $composerPackageManager = new \TYPO3\TestingFramework\Composer\ComposerPackageManager();
    $testingFrameworkPath = $composerPackageManager->getPackageInfo('typo3/testing-framework')->getRealPath();
    foreach ($frameworkExtension as $frameworkExtensionPath) {
        $packageInfo = $composerPackageManager->getPackageInfoWithFallback(rtrim($testingFrameworkPath, '/') . '/' . $frameworkExtensionPath);
        if ($packageInfo === null) {
            throw new \RuntimeException(
                sprintf(
                    'Could not preload "typo3/testing-framework" extension "%s".',
                    basename($frameworkExtensionPath),
                ),
                1734217315,
            );
        }
    }

    // Original typo3/testing-framework bootstrap
    $testbase = new \TYPO3\TestingFramework\Core\Testbase();
    $testbase->defineOriginalRootPath();
    $testbase->createDirectory(ORIGINAL_ROOT . 'typo3temp/var/tests');
    $testbase->createDirectory(ORIGINAL_ROOT . 'typo3temp/var/transient');
})();
