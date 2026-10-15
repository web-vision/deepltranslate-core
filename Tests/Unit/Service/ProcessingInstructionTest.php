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
        $runtimeCache = static::createStub(FrontendInterface::class);
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

        static::assertNull($runningInstruction);
        static::assertSame('tt_content', $subject->getProcessingTable());
        static::assertSame(1, $subject->getProcessingId());
        static::assertTrue($subject->isDeeplMode());
    }

    #[Test]
    public function nestedCommandRestoresInstructionOfRunningCommand(): void
    {
        $subject = $this->createSubject();

        $outerInstruction = $subject->startCommand('tt_content', 1);
        $nestedInstruction = $subject->startCommand('sys_file_reference', 9);
        static::assertSame('sys_file_reference', $subject->getProcessingTable());
        $subject->endCommand($nestedInstruction);

        static::assertSame('tt_content', $subject->getProcessingTable());
        static::assertSame(1, $subject->getProcessingId());
        static::assertTrue($subject->isDeeplMode());

        $subject->endCommand($outerInstruction);
        static::assertSame('tt_content', $subject->getProcessingTable());
    }

    #[Test]
    public function commandAfterEndedCommandIsNotNested(): void
    {
        $subject = $this->createSubject();

        $subject->endCommand($subject->startCommand('tt_content', 1));
        $runningInstruction = $subject->startCommand('pages', 2);
        $subject->endCommand($runningInstruction);

        static::assertNull($runningInstruction);
        static::assertSame('pages', $subject->getProcessingTable());
        static::assertSame(2, $subject->getProcessingId());
    }
}
