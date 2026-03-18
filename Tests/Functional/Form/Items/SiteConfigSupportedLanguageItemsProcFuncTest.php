<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Form\Items;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Form\Item\SiteConfigSupportedLanguageItemsProcFunc;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

#[CoversClass(SiteConfigSupportedLanguageItemsProcFunc::class)]
final class SiteConfigSupportedLanguageItemsProcFuncTest extends AbstractDeepLTestCase
{
    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = array_merge(
            $this->configurationToUseInTestInstance,
            require __DIR__ . '/../../Fixtures/ExtensionConfig.php'
        );

        parent::setUp();
    }

    #[Test]
    public function getSupportedLanguageFormFields(): void
    {
        $func = GeneralUtility::makeInstance(SiteConfigSupportedLanguageItemsProcFunc::class);
        $fieldConfig = [];

        $func->getSupportedLanguageForField($fieldConfig);

        $this->assertArrayHasKey('items', $fieldConfig);
        $this->assertTrue((count($fieldConfig['items']) > 2));
    }
}
