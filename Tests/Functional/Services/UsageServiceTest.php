<?php

declare(strict_types=1);

namespace WebVision\WvDeepltranslate\Tests\Functional\Services;

use DeepL\Usage;
use DeepL\UsageDetail;
use TYPO3\CMS\Core\Information\Typo3Version;
use WebVision\WvDeepltranslate\Service\DeeplService;
use WebVision\WvDeepltranslate\Service\ProcessingInstruction;
use WebVision\WvDeepltranslate\Service\UsageService;
use WebVision\WvDeepltranslate\Tests\Functional\AbstractDeepLTestCase;

final class UsageServiceTest extends AbstractDeepLTestCase
{
    protected ?string $sessionInitCharacterLimit = '20';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = array_merge(
            $this->configurationToUseInTestInstance,
            require __DIR__ . '/../Fixtures/ExtensionConfig.php'
        );

        parent::setUp();

        /** @var ProcessingInstruction $processingInstruction */
        $processingInstruction = $this->get(ProcessingInstruction::class);
        $processingInstruction->setProcessingInstruction(null, null, true);
    }

    /**
     * @test
     */
    public function classLoadable(): void
    {
        $usageService = $this->get(UsageService::class);

        static::assertInstanceOf(UsageService::class, $usageService);
    }

    /**
     * @test
     */
    public function usageReturnsValue(): void
    {
        /** @var UsageService $usageService */
        $usageService = $this->get(UsageService::class);

        $usage = $usageService->getCurrentUsage();

        static::assertInstanceOf(Usage::class, $usage);
    }

    /**
     * @test
     */
    public function limitExceedReturnsFalse(): void
    {
        /** @var UsageService $usageService */
        $usageService = $this->get(UsageService::class);

        static::assertFalse($usageService->checkTranslateLimitWillBeExceeded(''));
    }

    /**
     * @test
     */
    public function limitExceedReturnsTrueIfLimitIsReached(): void
    {
        $translateContent = 'proton beam';

        /** @var UsageService $usageService */
        $usageService = $this->get(UsageService::class);

        /** @var DeeplService $deeplService */
        $deeplService = $this->get(DeeplService::class);

        // Execute translation to check translation limit
        $responseObject = $deeplService->translateRequest(
            $translateContent,
            'DE',
            'EN'
        );

        $isLimitExceeded = $usageService->checkTranslateLimitWillBeExceeded($translateContent);
        static::assertTrue($isLimitExceeded);
    }

    /**
     * @test
     */
    public function checkHTMLMarkupsIsNotPartOfLimit(): void
    {
        $translateContent = 'proton beam';

        /** @var UsageService $usageService */
        $usageService = $this->get(UsageService::class);

        /** @var DeeplService $deeplService */
        $deeplService = $this->get(DeeplService::class);

        // Execute translation to check translation limit
        $responseObject = $deeplService->translateRequest(
            '<p>' . $translateContent . '</p>',
            'DE',
            'EN'
        );

        $usage = $usageService->getCurrentUsage();
        static::assertInstanceOf(Usage::class, $usage);
        $character = $usage->character;
        static::assertInstanceOf(UsageDetail::class, $character);
        static::assertEquals(strlen($translateContent), $character->count);
    }

    public static function numberFormatterLocalesDataProvider(): \Generator
    {
        yield 'Default formats to english' => [
            'user' => 1,
            'number' => 20000,
            'expectedFormat' => '20,000',
        ];
        yield 'BE uc lang "de" formats german' => [
            'user' => 2,
            'number' => 93254850,
            'expectedFormat' => '93.254.850',
        ];
    }
    /**
     * This test ensures that in PHP >=8.4 the NumberFormatter works correctly.
     * With migrated TYPO3 data there is the possibility that uc['lang'] is set to 'default',
     * which is no correct format for a locale the number formatter accepts. THis will lead
     * to an error during initialisation.
     *
     * @test
     * @dataProvider numberFormatterLocalesDataProvider
     */
    public function numberFormatRespectsLocalesAndDefault(
        int $user,
        int $number,
        string $expectedFormat
    ): void {
        // TYPO3 v11 seems not to respect the UC language setting, if other than default. skip this test for user 2
        if ((new Typo3Version())->getMajorVersion() <= 11 && $user === 2) {
            static::markTestSkipped('The issue only appears in PHP 8.4, which is not supported with TYPO3 11');
        }
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Pages.csv');
        $this->setUpBackendUser($user);
        /** @var UsageService $usageService */
        $usageService = $this->get(UsageService::class);

        $formatted = $usageService->formatNumber($number);
        static::assertEquals($expectedFormat, $formatted);
    }
}
