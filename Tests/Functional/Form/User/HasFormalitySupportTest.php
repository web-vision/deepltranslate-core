<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Form\User;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataProvider\EvaluateDisplayConditions;
use WebVision\Deepltranslate\Core\Form\User\HasFormalitySupport;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

#[CoversClass(HasFormalitySupport::class)]
class HasFormalitySupportTest extends AbstractDeepLTestCase
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
    public function hasFormalitySupportIsSupported(): void
    {
        /** @var HasFormalitySupport $subject */
        $subject = $this->getContainer()->get(HasFormalitySupport::class);
        $evaluateDisplayConditionsMock = $this->createMock(EvaluateDisplayConditions::class);

        $isFormalitySupported = $subject->checkFormalitySupport([
            'record' => [
                'deeplTargetLanguage' => [
                    'DE',
                ],
            ],
        ], $evaluateDisplayConditionsMock);

        $this->assertTrue($isFormalitySupported);
    }

    #[Test]
    public function hasFormalitySupportIsNotSupported(): void
    {
        /** @var HasFormalitySupport $subject */
        $subject = $this->getContainer()->get(HasFormalitySupport::class);
        $evaluateDisplayConditionsMock = $this->createMock(EvaluateDisplayConditions::class);

        $isFormalitySupported = $subject->checkFormalitySupport([
            'record' => [
                'deeplTargetLanguage' => [
                    'EN-GB',
                ],
            ],
        ], $evaluateDisplayConditionsMock);

        $this->assertFalse($isFormalitySupported);
    }

    #[Test]
    public function formalityIsNotSupportedWhenRecordNotExist(): void
    {
        /** @var HasFormalitySupport $subject */
        $subject = $this->getContainer()->get(HasFormalitySupport::class);
        $evaluateDisplayConditionsMock = $this->createMock(EvaluateDisplayConditions::class);

        $isFormalitySupported = $subject->checkFormalitySupport([], $evaluateDisplayConditionsMock);

        $this->assertFalse($isFormalitySupported);
    }

    #[Test]
    public function formalityIsNotSupportedWhenDeeplTargetLanguageNotExistOrEmpty(): void
    {
        /** @var HasFormalitySupport $subject */
        $subject = $this->getContainer()->get(HasFormalitySupport::class);
        $evaluateDisplayConditionsMock = $this->createMock(EvaluateDisplayConditions::class);

        $isFormalitySupported = $subject->checkFormalitySupport([
            'record' => [
                'deeplTargetLanguage' => [],
            ],
        ], $evaluateDisplayConditionsMock);

        $this->assertFalse($isFormalitySupported);
    }
}
