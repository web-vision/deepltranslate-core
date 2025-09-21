<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Services;

use DeepL\Usage;
use DeepL\UsageDetail;
use PHPUnit\Framework\Attributes\Test;
use WebVision\Deepltranslate\Core\Service\DeeplService;
use WebVision\Deepltranslate\Core\Service\ProcessingInstruction;
use WebVision\Deepltranslate\Core\Service\UsageService;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

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

    #[Test]
    public function classLoadable(): void
    {
        $usageService = $this->get(UsageService::class);

        $this->assertInstanceOf(UsageService::class, $usageService);
    }

    #[Test]
    public function usageReturnsValue(): void
    {
        /** @var UsageService $usageService */
        $usageService = $this->get(UsageService::class);

        $usage = $usageService->getCurrentUsage();

        $this->assertInstanceOf(Usage::class, $usage);
    }

    #[Test]
    public function limitExceedReturnsFalse(): void
    {
        /** @var UsageService $usageService */
        $usageService = $this->get(UsageService::class);

        $this->assertFalse($usageService->checkTranslateLimitWillBeExceeded(''));
    }

    #[Test]
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
        $this->assertTrue($isLimitExceeded);
    }

    #[Test]
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
        $this->assertInstanceOf(Usage::class, $usage);
        $character = $usage->character;
        $this->assertInstanceOf(UsageDetail::class, $character);
        $this->assertEquals(strlen($translateContent), $character->count);
    }
}
