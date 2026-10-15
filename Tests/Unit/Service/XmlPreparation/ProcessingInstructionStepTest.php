<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Service\XmlPreparation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\PreparationRecord;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\ProcessingInstructionStep;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\XmlPreparationStepInterface;

#[CoversClass(ProcessingInstructionStep::class)]
final class ProcessingInstructionStepTest extends AbstractXmlPreparationStepTestCase
{
    protected function createSubject(): XmlPreparationStepInterface
    {
        return new ProcessingInstructionStep();
    }

    protected function createPrecedingSteps(): array
    {
        return [];
    }

    public static function prepareDataProvider(): \Generator
    {
        yield 'processing instructions become numbered placeholders in document order' => [
            'html' => '<?xml version="1.0" encoding="UTF-8"?><svg><title>Logo</title></svg><p>a<?php echo 1; ?>b<em>c<?php echo 2; ?></em></p>',
            'expectedXml' => '<dlt-i dlt-n="0"/><svg><title>Logo</title></svg><p>a<dlt-i dlt-n="1"/>b<em>c<dlt-i dlt-n="2"/></em></p>',
        ];
        yield 'content without processing instructions is untouched' => [
            'html' => '<p>a<!-- ?php --> &lt;?php b</p>',
            'expectedXml' => '<p>a<!-- ?php --> &lt;?php b</p>',
        ];
    }

    #[Test]
    #[DataProvider('prepareDataProvider')]
    public function prepareReplacesProcessingInstructionsByPlaceholders(string $html, string $expectedXml): void
    {
        $content = $this->createContentFromHtml($html);

        $this->createSubject()->prepare($content, new PreparationRecord());

        static::assertSame($expectedXml, $this->serialize($content));
    }

    public static function revertDataProvider(): \Generator
    {
        yield 'placeholders become the processing instructions of the source' => [
            'translation' => '<p>b<dlt-i dlt-n="1"/>a<dlt-i dlt-n="0"/></p>',
            'source' => '<p>a<?php echo 1; ?>b<?go now?></p>',
            'expected' => '<p>b<?go now?>a<?php echo 1; ?></p>',
        ];
        yield 'a copy made by DeepL is removed' => [
            'translation' => '<p><dlt-i dlt-n="0"/>a<dlt-i dlt-n="0"/></p>',
            'source' => '<p>a<?php echo 1; ?></p>',
            'expected' => '<p><?php echo 1; ?>a</p>',
        ];
        yield 'a copy with another spelling of the number is removed' => [
            'translation' => '<p><dlt-i dlt-n="0"/>a<dlt-i dlt-n="00"/></p>',
            'source' => '<p>a<?php echo 1; ?></p>',
            'expected' => '<p><?php echo 1; ?>a</p>',
        ];
        yield 'a placeholder of unknown number is removed, text in it is kept' => [
            'translation' => '<p>a<dlt-i dlt-n="3">x</dlt-i><dlt-i dlt-n="y"/></p>',
            'source' => '<p>a<?php echo 1; ?></p>',
            'expected' => '<p>ax</p>',
        ];
    }

    #[Test]
    #[DataProvider('revertDataProvider')]
    public function revertRestoresTheProcessingInstructions(string $translation, string $source, string $expected): void
    {
        static::assertSame($expected, $this->revert($translation, $source));
    }
}
