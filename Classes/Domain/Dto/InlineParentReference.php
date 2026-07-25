<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Domain\Dto;

/**
 * Describes the connected mode inline (IRRE) relation between a child record and the record owning
 * it, resolved by {@see \WebVision\Deepltranslate\Core\Service\InlineRelationResolver}.
 */
final class InlineParentReference
{
    public function __construct(
        public readonly string $childTable,
        public readonly int $childUid,
        public readonly string $parentTable,
        public readonly string $parentField,
        public readonly int $parentUid,
        public readonly string $foreignField,
    ) {
    }

    /**
     * Identifier to compare two resolved references, used to detect ambiguous relation configurations.
     */
    public function getIdentifier(): string
    {
        return sprintf('%s.%s.%d', $this->parentTable, $this->parentField, $this->parentUid);
    }
}
