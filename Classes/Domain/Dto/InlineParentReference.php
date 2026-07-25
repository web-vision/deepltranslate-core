<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Domain\Dto;

/**
 * Describes the connected mode inline (IRRE) relation between a child record and the record owning
 * it, resolved by {@see \WebVision\Deepltranslate\Core\Service\InlineRelationResolver}.
 */
final readonly class InlineParentReference
{
    public function __construct(
        public string $childTable,
        public int $childUid,
        public string $parentTable,
        public string $parentField,
        public int $parentUid,
        public string $foreignField,
    ) {}

    /**
     * Identifier to compare two resolved references, used to detect ambiguous relation configurations.
     */
    public function getIdentifier(): string
    {
        return sprintf('%s.%s.%d', $this->parentTable, $this->parentField, $this->parentUid);
    }
}
