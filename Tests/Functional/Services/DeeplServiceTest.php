<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Services;

use DeepL\Language;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use WebVision\Deepltranslate\Core\Domain\Dto\TranslateContext;
use WebVision\Deepltranslate\Core\Service\DeeplService;
use WebVision\Deepltranslate\Core\Service\ProcessingInstruction;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

#[CoversClass(DeeplService::class)]
final class DeeplServiceTest extends AbstractDeepLTestCase
{
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
     * @deprecated if the @see DeeplService::translateRequest() function has been removed
     */
    #[Test]
    public function translateContentFromDeToEn(): void
    {
        /** @var DeeplService $deeplService */
        $deeplService = $this->get(DeeplService::class);

        $translateContent = $deeplService->translateRequest(
            self::EXAMPLE_TEXT['de'],
            'EN-GB',
            'DE'
        );

        $this->assertSame(self::EXAMPLE_TEXT['en'], $translateContent);
    }

    /**
     * @deprecated if the @see DeeplService::translateRequest() function has been removed
     */
    #[Test]
    public function translateContentFromEnToDe(): void
    {
        $translateContent = 'proton beam';
        $expectedTranslation = 'Protonenstrahl';
        /** @var DeeplService $deeplService */
        $deeplService = $this->get(DeeplService::class);

        $translateContent = $deeplService->translateRequest(
            $translateContent,
            'DE',
            'EN'
        );

        $this->assertSame($expectedTranslation, $translateContent);
    }

    /**
     * @deprecated entfällt wenn die Funktion @see DeeplService::translateRequest() entfernt wurde
     */
    #[Test]
    public function translateContentWithAutoDetectSourceParam(): void
    {
        $translateContent = 'proton beam';
        $expectedTranslation = 'Protonenstrahl';
        /** @var DeeplService $deeplService */
        $deeplService = $this->get(DeeplService::class);

        $translateContent = $deeplService->translateRequest(
            $translateContent,
            'DE',
            'auto'
        );

        $this->assertSame($expectedTranslation, $translateContent);
    }

    #[Test]
    public function translateContentWithTranslateContextFromDeToEn(): void
    {
        /** @var DeeplService $deeplService */
        $deeplService = $this->get(DeeplService::class);

        $translateContext = new TranslateContext('Protonenstrahl');
        $translateContext->setSourceLanguageCode('DE');
        $translateContext->setTargetLanguageCode('EN-GB');

        $translateContent = $deeplService->translateContent($translateContext);

        $this->assertSame('proton beam', $translateContent);
    }

    #[Test]
    public function translateContentWithTranslateContextFromEnToDe(): void
    {
        /** @var DeeplService $deeplService */
        $deeplService = $this->get(DeeplService::class);

        $translateContext = new TranslateContext('proton beam');
        $translateContext->setSourceLanguageCode('EN');
        $translateContext->setTargetLanguageCode('DE');

        $translateContent = $deeplService->translateContent($translateContext);

        $this->assertSame('Protonenstrahl', $translateContent);
    }

    #[Test]
    public function translateContentWithTranslateContextWithAutoDetectSourceParam(): void
    {
        /** @var DeeplService $deeplService */
        $deeplService = $this->get(DeeplService::class);

        $translateContext = new TranslateContext('proton beam');
        $translateContext->setSourceLanguageCode('auto');
        $translateContext->setTargetLanguageCode('DE');

        $translateContent = $deeplService->translateContent($translateContext);

        $this->assertSame('Protonenstrahl', $translateContent);
    }

    #[Test]
    public function checkSupportedTargetLanguages(): void
    {
        /** @var DeeplService $deeplService */
        $deeplService = $this->get(DeeplService::class);

        $this->assertContainsOnlyInstancesOf(Language::class, $deeplService->getSupportLanguage()['target']);

        $this->assertEquals('EN-GB', $deeplService->detectTargetLanguage('EN-GB')->code);
        $this->assertEquals('EN-US', $deeplService->detectTargetLanguage('EN-US')->code);
        $this->assertEquals('DE', $deeplService->detectTargetLanguage('DE')->code);
        $this->assertEquals('UK', $deeplService->detectTargetLanguage('UK')->code);
        $this->assertNull($deeplService->detectTargetLanguage('EN'));
        $this->assertNull($deeplService->detectTargetLanguage('BS'));
    }

    #[Test]
    public function checkIsTargetLanguageSupported(): void
    {
        /** @var DeeplService $deeplService */
        $deeplService = $this->get(DeeplService::class);

        $this->assertTrue($deeplService->isTargetLanguageSupported('DE'));
        // We should avoid using a real existing language here, as the tests will fail,
        // if the language gets supported by DeepL and the mock server is updated.
        $this->assertFalse($deeplService->isTargetLanguageSupported('BS'));
    }

    #[Test]
    public function checkSupportedSourceLanguages(): void
    {
        /** @var DeeplService $deeplService */
        $deeplService = $this->get(DeeplService::class);

        $this->assertEquals('DE', $deeplService->detectSourceLanguage('DE')->code);
        $this->assertEquals('UK', $deeplService->detectSourceLanguage('UK')->code);
        $this->assertEquals('EN', $deeplService->detectSourceLanguage('EN')->code);
        $this->assertNull($deeplService->detectSourceLanguage('EN-GB'));
        $this->assertNull($deeplService->detectSourceLanguage('EN-US'));
        $this->assertNull($deeplService->detectSourceLanguage('BS'));
    }

    #[Test]
    public function checkIsSourceLanguageSupported(): void
    {
        /** @var DeeplService $deeplService */
        $deeplService = $this->get(DeeplService::class);

        $this->assertTrue($deeplService->isSourceLanguageSupported('DE'));
    }

    #[Test]
    public function checkHasLanguageFormalitySupport(): void
    {
        /** @var DeeplService $deeplService */
        $deeplService = $this->get(DeeplService::class);

        $hasFormalitySupport = $deeplService->hasLanguageFormalitySupport('DE');
        $this->assertTrue($hasFormalitySupport);
        $hasNotFormalitySupport = $deeplService->hasLanguageFormalitySupport('EN-GB');
        $this->assertFalse($hasNotFormalitySupport);
    }

}
