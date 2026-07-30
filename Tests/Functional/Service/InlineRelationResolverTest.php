<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use WebVision\Deepltranslate\Core\Domain\Dto\InlineParentReference;
use WebVision\Deepltranslate\Core\Domain\Dto\InlineParentResolution;
use WebVision\Deepltranslate\Core\Domain\Enum\InlineParentState;
use WebVision\Deepltranslate\Core\Service\InlineRelationResolver;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

#[CoversClass(InlineRelationResolver::class)]
#[CoversClass(InlineParentReference::class)]
#[CoversClass(InlineParentResolution::class)]
final class InlineRelationResolverTest extends AbstractDeepLTestCase
{
    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = __DIR__ . '/../Fixtures/Extensions/test_inline_relations';

        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/inlineRelations.csv');
    }

    #[Test]
    public function tablesUsableAsInlineChildAreDetectedWithoutARecord(): void
    {
        $resolver = $this->get(InlineRelationResolver::class);

        $this->assertTrue($resolver->isPossibleInlineChildTable('tx_testinlinerelations_child_declared'));
        $this->assertTrue($resolver->isPossibleInlineChildTable('tx_testinlinerelations_child_undeclared'));
        // Shared table: `tt_content` is a normal content element on a page, but is also usable as an
        // inline child - here through `tx_testinlinerelations_contentparent.content_elements`, the
        // same shape EXT:news uses for `tx_news_domain_model_news.content_elements`.
        $this->assertTrue($resolver->isPossibleInlineChildTable('tt_content'));
        // Shipped by TYPO3 itself, for example through `tt_content.image`
        $this->assertTrue($resolver->isPossibleInlineChildTable('sys_file_reference'));
    }

    #[Test]
    public function tablesNotUsableAsInlineChildAreNotDetected(): void
    {
        $resolver = $this->get(InlineRelationResolver::class);

        $this->assertFalse($resolver->isPossibleInlineChildTable('tx_testinlinerelations_parent'));
        $this->assertFalse($resolver->isPossibleInlineChildTable('tx_testinlinerelations_contentparent'));
        $this->assertFalse($resolver->isPossibleInlineChildTable('pages'));
        $this->assertFalse($resolver->isPossibleInlineChildTable('table_which_does_not_exist'));
    }

    #[Test]
    public function inlineChildWithTcaConfiguredPointerFieldIsResolved(): void
    {
        $resolution = $this->get(InlineRelationResolver::class)
            ->resolveParentReference('tx_testinlinerelations_child_declared', 1);

        $this->assertSame(InlineParentState::Resolved, $resolution->state);
        $reference = $resolution->reference;
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
        $resolution = $this->get(InlineRelationResolver::class)
            ->resolveParentReference('tx_testinlinerelations_child_undeclared', 1);

        $this->assertSame(InlineParentState::Resolved, $resolution->state);
        $reference = $resolution->reference;
        $this->assertInstanceOf(InlineParentReference::class, $reference);
        $this->assertSame('tx_testinlinerelations_parent', $reference->parentTable);
        $this->assertSame('children_undeclared', $reference->parentField);
        $this->assertSame(1, $reference->parentUid);
    }

    /**
     * The real-world case behind issue #503: `tt_content` used as an inline child (like EXT:news
     * `content_elements`) must resolve to its owning record.
     */
    #[Test]
    public function sharedTableRecordUsedAsInlineChildIsResolved(): void
    {
        $resolution = $this->get(InlineRelationResolver::class)
            ->resolveParentReference('tt_content', 10);

        $this->assertSame(InlineParentState::Resolved, $resolution->state);
        $reference = $resolution->reference;
        $this->assertInstanceOf(InlineParentReference::class, $reference);
        $this->assertSame('tt_content', $reference->childTable);
        $this->assertSame('tx_testinlinerelations_contentparent', $reference->parentTable);
        $this->assertSame('content_elements', $reference->parentField);
        $this->assertSame(1, $reference->parentUid);
        $this->assertSame('tx_testinlinerelations_related', $reference->foreignField);
    }

    /**
     * The counterpart: a `tt_content` element placed directly on a page shares its table with inline
     * children but is not one, and must be treated as a normal record - not routed through a parent.
     */
    #[Test]
    public function sharedTableRecordNotUsedAsInlineChildIsNotInlineChild(): void
    {
        $resolution = $this->get(InlineRelationResolver::class)
            ->resolveParentReference('tt_content', 20);

        $this->assertSame(InlineParentState::NotInlineChild, $resolution->state);
        $this->assertNull($resolution->reference);
    }

    #[Test]
    public function recordWhichIsNoInlineChildIsNotInlineChild(): void
    {
        $resolver = $this->get(InlineRelationResolver::class);

        $this->assertSame(
            InlineParentState::NotInlineChild,
            $resolver->resolveParentReference('tx_testinlinerelations_parent', 1)->state
        );
        $this->assertSame(
            InlineParentState::NotInlineChild,
            $resolver->resolveParentReference('pages', 1)->state
        );
    }

    #[Test]
    public function inlineChildWithoutPointerValueIsNotInlineChild(): void
    {
        $this->assertSame(
            InlineParentState::NotInlineChild,
            $this->get(InlineRelationResolver::class)
                ->resolveParentReference('tx_testinlinerelations_child_declared', 2)->state
        );
    }

    #[Test]
    public function inlineChildPointingToNonExistingParentReportsParentMissing(): void
    {
        $this->assertSame(
            InlineParentState::ParentMissing,
            $this->get(InlineRelationResolver::class)
                ->resolveParentReference('tx_testinlinerelations_child_declared', 3)->state
        );
    }

    #[Test]
    public function childClaimedByTwoParentRelationsReportsAmbiguous(): void
    {
        $this->assertSame(
            InlineParentState::Ambiguous,
            $this->get(InlineRelationResolver::class)
                ->resolveParentReference('tx_testinlinerelations_child_declared', 4)->state
        );
    }

    #[Test]
    public function nonExistingRecordIsNotInlineChild(): void
    {
        $resolver = $this->get(InlineRelationResolver::class);

        $this->assertSame(
            InlineParentState::NotInlineChild,
            $resolver->resolveParentReference('tx_testinlinerelations_child_declared', 99)->state
        );
        $this->assertSame(
            InlineParentState::NotInlineChild,
            $resolver->resolveParentReference('tx_testinlinerelations_child_declared', 0)->state
        );
        $this->assertSame(
            InlineParentState::NotInlineChild,
            $resolver->resolveParentReference('table_which_does_not_exist', 1)->state
        );
    }

    #[Test]
    public function inlineChildOfANonTranslatableParentIsNotHandedOverToTheParent(): void
    {
        $resolver = $this->get(InlineRelationResolver::class);

        // `sys_file` owns `sys_file_metadata` through a `foreign_field` pointer, so the metadata
        // record really is an inline child in connected mode - but `sys_file` itself has no
        // `languageField`/`transOrigPointerField` and can never carry a localization. Handing the
        // localization over to the parent would create nothing at all, so such a child has to be
        // reported as `ParentNotTranslatable` and localized on its own.
        $resolution = $resolver->resolveParentReference('sys_file_metadata', 1);

        $this->assertSame(InlineParentState::ParentNotTranslatable, $resolution->state);
        $this->assertNull($resolution->reference);
        $this->assertFalse($resolution->isResolved());
    }

    #[Test]
    public function tableOwnedByANonTranslatableParentIsStillAPossibleInlineChildTable(): void
    {
        $resolver = $this->get(InlineRelationResolver::class);

        // Unchanged behaviour: the table-level check only answers whether records of that table can
        // be inline children at all. Whether the concrete parent is translatable is decided per
        // record in `resolveParentReference()`.
        $this->assertTrue($resolver->isPossibleInlineChildTable('sys_file_metadata'));
    }
}
