<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Service\XmlPreparation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\PreparationRecord;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\ReferenceStep;
use WebVision\Deepltranslate\Core\Service\XmlPreparation\XmlPreparationStepInterface;

#[CoversClass(ReferenceStep::class)]
#[CoversClass(PreparationRecord::class)]
final class ReferenceStepTest extends AbstractXmlPreparationStepTestCase
{
    protected function createSubject(): XmlPreparationStepInterface
    {
        return new ReferenceStep();
    }

    protected function createPrecedingSteps(): array
    {
        return [];
    }

    #[Test]
    public function prepareNumbersInlineElementsInDocumentOrderAndRecordsTheirNames(): void
    {
        $content = $this->createContent('<p>a <strong>b <a href="#">c</a></strong><br/><sup>1</sup></p><ul><li><em>e</em></li></ul>');
        $record = new PreparationRecord();

        $this->createSubject()->prepare($content, $record);

        static::assertSame(
            '<p>a <strong dlt-r="0">b <a href="#" dlt-r="1">c</a></strong><br/><sup dlt-r="2">1</sup></p><ul><li><em dlt-r="3">e</em></li></ul>',
            $this->serialize($content)
        );
        static::assertSame(['strong', 'a', 'sup', 'em', null], [$record->name(0), $record->name(1), $record->name(2), $record->name(3), $record->name(4)]);
    }

    #[Test]
    public function revertRemovesTheReferenceNumbers(): void
    {
        static::assertSame(
            '<p><strong>b <a href="#">c</a></strong><dlt-s>d</dlt-s></p>',
            $this->revert('<p><strong dlt-r="0">b <a href="#" dlt-r="1">c</a></strong><dlt-s dlt-r="2">d</dlt-s></p>')
        );
    }
}
