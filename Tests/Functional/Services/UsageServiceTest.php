<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Services;

use DeepL\Usage;
use DeepL\UsageDetail;
use PHPUnit\Framework\Attributes\RunClassInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use WebVision\Deepltranslate\Core\Domain\Dto\TranslateContext;
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

        self::assertInstanceOf(UsageService::class, $usageService);
    }

    #[Test]
    public function usageReturnsValue(): void
    {
        /** @var UsageService $usageService */
        $usageService = $this->get(UsageService::class);

        $usage = $usageService->getCurrentUsage();

        self::assertInstanceOf(Usage::class, $usage);
    }

    #[Test]
    public function limitExceedReturnsFalse(): void
    {
        /** @var UsageService $usageService */
        $usageService = $this->get(UsageService::class);

        self::assertFalse($usageService->checkTranslateLimitWillBeExceeded(''));
    }

    #[Test]
    public function limitExceedReturnsTrueIfLimitIsReached(): void
    {
        $translateContent = self::EXAMPLE_TEXT['en'];

        /** @var UsageService $usageService */
        $usageService = $this->get(UsageService::class);

        /** @var DeeplService $deeplService */
        $deeplService = $this->get(DeeplService::class);

        // Execute translation to check translation limit
        $translateContext = new TranslateContext($translateContent);
        $translateContext->setSourceLanguageCode('EN');
        $translateContext->setTargetLanguageCode('DE');
        $translatedContent = $deeplService->translateContent($translateContext);

        self::assertEquals(self::EXAMPLE_TEXT['de'], $translatedContent);
        $isLimitExceeded = $usageService->checkTranslateLimitWillBeExceeded($translateContent);
        self::assertTrue($isLimitExceeded);
    }

    #[Test]
    public function checkHTMLMarkupsIsNotPartOfLimit(): void
    {
        $translateContent = self::EXAMPLE_TEXT['en'];

        /** @var UsageService $usageService */
        $usageService = $this->get(UsageService::class);

        /** @var DeeplService $deeplService */
        $deeplService = $this->get(DeeplService::class);

        $translateContext = new TranslateContext('<p>' . $translateContent . '</p>');
        $translateContext->setSourceLanguageCode('EN');
        $translateContext->setTargetLanguageCode('DE');
        // Execute translation to check translation limit
        // @todo at the moment the mock server returns an empty result, when the
        //       translation string is given with HTML tags, but increases character
        //       usage. I have no idea, why this is happening, but with this behaviour
        //       there is no possibility checking the response onto valid value.
        $response = $deeplService->translateContent($translateContext);


        $usage = $usageService->getCurrentUsage();
        self::assertInstanceOf(Usage::class, $usage);
        $character = $usage->character;
        self::assertInstanceOf(UsageDetail::class, $character);
        self::assertEquals(strlen($translateContent), $character->count);
    }
}
