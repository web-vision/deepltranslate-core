<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use WebVision\Deepltranslate\Core\Domain\Dto\InlineParentReference;
use WebVision\Deepltranslate\Core\Service\InlineRelationResolver;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

#[CoversClass(InlineRelationResolver::class)]
#[CoversClass(InlineParentReference::class)]
final class InlineRelationResolverTest extends AbstractDeepLTestCase
{
    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = __DIR__ . '/../Fixtures/Extensions/test_inline_relations';

        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineRelations.csv');
    }

    #[Test]
    public function inlineChildWithTcaConfiguredPointerFieldIsResolved(): void
    {
        $reference = $this->get(InlineRelationResolver::class)
            ->resolveParentReference('tx_testinlinerelations_child_declared', 1);

        $this->assertInstanceOf(InlineParentReference::class, $reference);
        $this->assertSame('tx_testinlinerelations_child_declared', $reference->childTable);
        $this->assertSame(1, $reference->childUid);
        $this->assertSame('tx_testinlinerelations_parent', $reference->parentTable);
        $this->assertSame('children_declared', $reference->parentField);
        $this->assertSame(1, $reference->parentUid);
        $this->assertSame('parentid', $reference->foreignField);
    }

    #[Test]
    public function inlineChildWithoutTcaConfiguredPointerFieldIsResolved(): void
    {
        $reference = $this->get(InlineRelationResolver::class)
            ->resolveParentReference('tx_testinlinerelations_child_undeclared', 1);

        $this->assertInstanceOf(InlineParentReference::class, $reference);
        $this->assertSame('tx_testinlinerelations_parent', $reference->parentTable);
        $this->assertSame('children_undeclared', $reference->parentField);
        $this->assertSame(1, $reference->parentUid);
    }

    #[Test]
    public function recordWhichIsNoInlineChildIsNotResolved(): void
    {
        $resolver = $this->get(InlineRelationResolver::class);

        $this->assertNull($resolver->resolveParentReference('tx_testinlinerelations_parent', 1));
        $this->assertNull($resolver->resolveParentReference('pages', 1));
    }

    #[Test]
    public function inlineChildWithoutPointerValueIsNotResolved(): void
    {
        $this->assertNull(
            $this->get(InlineRelationResolver::class)
                ->resolveParentReference('tx_testinlinerelations_child_declared', 2)
        );
    }

    #[Test]
    public function inlineChildPointingToNonExistingParentIsNotResolved(): void
    {
        $this->assertNull(
            $this->get(InlineRelationResolver::class)
                ->resolveParentReference('tx_testinlinerelations_child_declared', 3)
        );
    }

    #[Test]
    public function nonExistingRecordIsNotResolved(): void
    {
        $resolver = $this->get(InlineRelationResolver::class);

        $this->assertNull($resolver->resolveParentReference('tx_testinlinerelations_child_declared', 99));
        $this->assertNull($resolver->resolveParentReference('tx_testinlinerelations_child_declared', 0));
        $this->assertNull($resolver->resolveParentReference('table_which_does_not_exist', 1));
    }
}
