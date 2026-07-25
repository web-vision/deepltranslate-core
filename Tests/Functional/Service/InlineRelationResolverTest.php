<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Service;

use WebVision\Deepltranslate\Core\Domain\Dto\InlineParentReference;
use WebVision\Deepltranslate\Core\Service\InlineRelationResolver;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

final class InlineRelationResolverTest extends AbstractDeepLTestCase
{
    /**
     * @var non-empty-string[]
     */
    protected array $testExtensionsToLoad = [
        'web-vision/deepl-base',
        'web-vision/deeplcom-deepl-php',
        'web-vision/deepltranslate-core',
        __DIR__ . '/../Fixtures/Extensions/test_services_override',
        __DIR__ . '/../Fixtures/Extensions/test_inline_relations',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineRelations.csv');
    }

    /**
     * @test
     */
    public function tablesUsableAsInlineChildAreDetectedWithoutARecord(): void
    {
        $resolver = $this->get(InlineRelationResolver::class);

        static::assertTrue($resolver->isPossibleInlineChildTable('tx_testinlinerelations_child_declared'));
        static::assertTrue($resolver->isPossibleInlineChildTable('tx_testinlinerelations_child_undeclared'));
        // Shipped by TYPO3 itself, for example through `tt_content.image`
        static::assertTrue($resolver->isPossibleInlineChildTable('sys_file_reference'));
    }

    /**
     * @test
     */
    public function tablesNotUsableAsInlineChildAreNotDetected(): void
    {
        $resolver = $this->get(InlineRelationResolver::class);

        static::assertFalse($resolver->isPossibleInlineChildTable('tx_testinlinerelations_parent'));
        static::assertFalse($resolver->isPossibleInlineChildTable('pages'));
        static::assertFalse($resolver->isPossibleInlineChildTable('table_which_does_not_exist'));
    }

    /**
     * @test
     */
    public function inlineChildWithTcaConfiguredPointerFieldIsResolved(): void
    {
        $reference = $this->get(InlineRelationResolver::class)
            ->resolveParentReference('tx_testinlinerelations_child_declared', 1);

        static::assertInstanceOf(InlineParentReference::class, $reference);
        static::assertSame('tx_testinlinerelations_child_declared', $reference->childTable);
        static::assertSame(1, $reference->childUid);
        static::assertSame('tx_testinlinerelations_parent', $reference->parentTable);
        static::assertSame('children_declared', $reference->parentField);
        static::assertSame(1, $reference->parentUid);
        static::assertSame('parentid', $reference->foreignField);
    }

    /**
     * @test
     */
    public function inlineChildWithoutTcaConfiguredPointerFieldIsResolved(): void
    {
        $reference = $this->get(InlineRelationResolver::class)
            ->resolveParentReference('tx_testinlinerelations_child_undeclared', 1);

        static::assertInstanceOf(InlineParentReference::class, $reference);
        static::assertSame('tx_testinlinerelations_parent', $reference->parentTable);
        static::assertSame('children_undeclared', $reference->parentField);
        static::assertSame(1, $reference->parentUid);
    }

    /**
     * @test
     */
    public function recordWhichIsNoInlineChildIsNotResolved(): void
    {
        $resolver = $this->get(InlineRelationResolver::class);

        static::assertNull($resolver->resolveParentReference('tx_testinlinerelations_parent', 1));
        static::assertNull($resolver->resolveParentReference('pages', 1));
    }

    /**
     * @test
     */
    public function inlineChildWithoutPointerValueIsNotResolved(): void
    {
        static::assertNull(
            $this->get(InlineRelationResolver::class)
                ->resolveParentReference('tx_testinlinerelations_child_declared', 2)
        );
    }

    /**
     * @test
     */
    public function inlineChildPointingToNonExistingParentIsNotResolved(): void
    {
        static::assertNull(
            $this->get(InlineRelationResolver::class)
                ->resolveParentReference('tx_testinlinerelations_child_declared', 3)
        );
    }

    /**
     * @test
     */
    public function nonExistingRecordIsNotResolved(): void
    {
        $resolver = $this->get(InlineRelationResolver::class);

        static::assertNull($resolver->resolveParentReference('tx_testinlinerelations_child_declared', 99));
        static::assertNull($resolver->resolveParentReference('tx_testinlinerelations_child_declared', 0));
        static::assertNull($resolver->resolveParentReference('table_which_does_not_exist', 1));
    }
}
