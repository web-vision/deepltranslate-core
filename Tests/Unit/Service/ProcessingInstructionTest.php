<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WebVision\Deepltranslate\Core\Service\ProcessingInstruction;

#[CoversClass(ProcessingInstruction::class)]
final class ProcessingInstructionTest extends UnitTestCase
{
    private function createSubject(): ProcessingInstruction
    {
        /** @var array<string, mixed> $entries */
        $entries = [];
        $runtimeCache = $this->createStub(FrontendInterface::class);
        $runtimeCache->method('has')->willReturnCallback(
            static function (string $identifier) use (&$entries): bool {
                return array_key_exists($identifier, $entries);
            }
        );
        $runtimeCache->method('get')->willReturnCallback(
            static function (string $identifier) use (&$entries): mixed {
                return $entries[$identifier] ?? false;
            }
        );
        $runtimeCache->method('set')->willReturnCallback(
            static function (string $identifier, mixed $data) use (&$entries): void {
                $entries[$identifier] = $data;
            }
        );
        return new ProcessingInstruction($runtimeCache);
    }

    #[Test]
    public function commandNotIssuedWithinAnotherOneKeepsItsInstruction(): void
    {
        $subject = $this->createSubject();

        $runningInstruction = $subject->startCommand('tt_content', 1);
        $subject->endCommand($runningInstruction);

        $this->assertNull($runningInstruction);
        $this->assertSame('tt_content', $subject->getProcessingTable());
        $this->assertSame(1, $subject->getProcessingId());
        $this->assertTrue($subject->isDeeplMode());
    }

    #[Test]
    public function nestedCommandRestoresInstructionOfRunningCommand(): void
    {
        $subject = $this->createSubject();

        $outerInstruction = $subject->startCommand('tt_content', 1);
        $nestedInstruction = $subject->startCommand('sys_file_reference', 9);
        $this->assertSame('sys_file_reference', $subject->getProcessingTable());
        $subject->endCommand($nestedInstruction);

        $this->assertSame('tt_content', $subject->getProcessingTable());
        $this->assertSame(1, $subject->getProcessingId());
        $this->assertTrue($subject->isDeeplMode());

        $subject->endCommand($outerInstruction);
        $this->assertSame('tt_content', $subject->getProcessingTable());
    }

    #[Test]
    public function commandAfterEndedCommandIsNotNested(): void
    {
        $subject = $this->createSubject();

        $subject->endCommand($subject->startCommand('tt_content', 1));
        $runningInstruction = $subject->startCommand('pages', 2);
        $subject->endCommand($runningInstruction);

        $this->assertNull($runningInstruction);
        $this->assertSame('pages', $subject->getProcessingTable());
        $this->assertSame(2, $subject->getProcessingId());
    }
}
