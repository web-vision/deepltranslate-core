<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Service;

use WebVision\Deepltranslate\Core\Domain\Dto\InlineParentReference;
use WebVision\Deepltranslate\Core\Domain\Enum\InlineParentState;
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
        // Shared table: `tt_content` is a normal content element on a page, but is also usable as an
        // inline child - here through `tx_testinlinerelations_contentparent.content_elements`, the
        // same shape EXT:news uses for `tx_news_domain_model_news.content_elements`.
        static::assertTrue($resolver->isPossibleInlineChildTable('tt_content'));
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
        static::assertFalse($resolver->isPossibleInlineChildTable('tx_testinlinerelations_contentparent'));
        static::assertFalse($resolver->isPossibleInlineChildTable('pages'));
        static::assertFalse($resolver->isPossibleInlineChildTable('table_which_does_not_exist'));
    }

    /**
     * @test
     */
    public function inlineChildWithTcaConfiguredPointerFieldIsResolved(): void
    {
        $resolution = $this->get(InlineRelationResolver::class)
            ->resolveParentReference('tx_testinlinerelations_child_declared', 1);

        static::assertSame(InlineParentState::Resolved, $resolution->state);
        $reference = $resolution->reference;
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
        $resolution = $this->get(InlineRelationResolver::class)
            ->resolveParentReference('tx_testinlinerelations_child_undeclared', 1);

        static::assertSame(InlineParentState::Resolved, $resolution->state);
        $reference = $resolution->reference;
        static::assertInstanceOf(InlineParentReference::class, $reference);
        static::assertSame('tx_testinlinerelations_parent', $reference->parentTable);
        static::assertSame('children_undeclared', $reference->parentField);
        static::assertSame(1, $reference->parentUid);
    }

    /**
     * The real-world case behind issue #503: `tt_content` used as an inline child (like EXT:news
     * `content_elements`) must resolve to its owning record.
     *
     * @test
     */
    public function sharedTableRecordUsedAsInlineChildIsResolved(): void
    {
        $resolution = $this->get(InlineRelationResolver::class)
            ->resolveParentReference('tt_content', 10);

        static::assertSame(InlineParentState::Resolved, $resolution->state);
        $reference = $resolution->reference;
        static::assertInstanceOf(InlineParentReference::class, $reference);
        static::assertSame('tt_content', $reference->childTable);
        static::assertSame('tx_testinlinerelations_contentparent', $reference->parentTable);
        static::assertSame('content_elements', $reference->parentField);
        static::assertSame(1, $reference->parentUid);
        static::assertSame('tx_testinlinerelations_related', $reference->foreignField);
    }

    /**
     * The counterpart: a `tt_content` element placed directly on a page shares its table with inline
     * children but is not one, and must be treated as a normal record - not routed through a parent.
     *
     * @test
     */
    public function sharedTableRecordNotUsedAsInlineChildIsNotInlineChild(): void
    {
        $resolution = $this->get(InlineRelationResolver::class)
            ->resolveParentReference('tt_content', 20);

        static::assertSame(InlineParentState::NotInlineChild, $resolution->state);
        static::assertNull($resolution->reference);
    }

    /**
     * @test
     */
    public function recordWhichIsNoInlineChildIsNotInlineChild(): void
    {
        $resolver = $this->get(InlineRelationResolver::class);

        static::assertSame(
            InlineParentState::NotInlineChild,
            $resolver->resolveParentReference('tx_testinlinerelations_parent', 1)->state
        );
        static::assertSame(
            InlineParentState::NotInlineChild,
            $resolver->resolveParentReference('pages', 1)->state
        );
    }

    /**
     * @test
     */
    public function inlineChildWithoutPointerValueIsNotInlineChild(): void
    {
        static::assertSame(
            InlineParentState::NotInlineChild,
            $this->get(InlineRelationResolver::class)
                ->resolveParentReference('tx_testinlinerelations_child_declared', 2)->state
        );
    }

    /**
     * @test
     */
    public function inlineChildPointingToNonExistingParentReportsParentMissing(): void
    {
        static::assertSame(
            InlineParentState::ParentMissing,
            $this->get(InlineRelationResolver::class)
                ->resolveParentReference('tx_testinlinerelations_child_declared', 3)->state
        );
    }

    /**
     * @test
     */
    public function childClaimedByTwoParentRelationsReportsAmbiguous(): void
    {
        static::assertSame(
            InlineParentState::Ambiguous,
            $this->get(InlineRelationResolver::class)
                ->resolveParentReference('tx_testinlinerelations_child_declared', 4)->state
        );
    }

    /**
     * @test
     */
    public function nonExistingRecordIsNotInlineChild(): void
    {
        $resolver = $this->get(InlineRelationResolver::class);

        static::assertSame(
            InlineParentState::NotInlineChild,
            $resolver->resolveParentReference('tx_testinlinerelations_child_declared', 99)->state
        );
        static::assertSame(
            InlineParentState::NotInlineChild,
            $resolver->resolveParentReference('tx_testinlinerelations_child_declared', 0)->state
        );
        static::assertSame(
            InlineParentState::NotInlineChild,
            $resolver->resolveParentReference('table_which_does_not_exist', 1)->state
        );
    }

    /**
     * @test
     */
    public function inlineChildOfANonTranslatableParentIsNotHandedOverToTheParent(): void
    {
        $resolver = $this->get(InlineRelationResolver::class);

        // `sys_file` owns `sys_file_metadata` through a `foreign_field` pointer, so the metadata
        // record really is an inline child in connected mode - but `sys_file` itself has no
        // `languageField`/`transOrigPointerField` and can never carry a localization. Handing the
        // localization over to the parent would create nothing at all, so such a child has to be
        // reported as `ParentNotTranslatable` and localized on its own.
        $resolution = $resolver->resolveParentReference('sys_file_metadata', 1);

        static::assertSame(InlineParentState::ParentNotTranslatable, $resolution->state);
        static::assertNull($resolution->reference);
        static::assertFalse($resolution->isResolved());
    }

    /**
     * @test
     */
    public function tableOwnedByANonTranslatableParentIsStillAPossibleInlineChildTable(): void
    {
        $resolver = $this->get(InlineRelationResolver::class);

        // Unchanged behaviour: the table-level check only answers whether records of that table can
        // be inline children at all. Whether the concrete parent is translatable is decided per
        // record in `resolveParentReference()`.
        static::assertTrue($resolver->isPossibleInlineChildTable('sys_file_metadata'));
    }
}
