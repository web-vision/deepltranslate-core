<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Domain\Dto;

use WebVision\Deepltranslate\Core\Domain\Enum\InlineParentState;

/**
 * Result of resolving the connected mode inline (IRRE) parent of a record, carrying both the
 * {@see InlineParentState outcome} and, for a resolved relation, the {@see InlineParentReference}.
 *
 * The state lets the caller tell a normal standalone record ({@see InlineParentState::NotInlineChild},
 * silent) apart from a broken relation configuration ({@see InlineParentState::Ambiguous},
 * {@see InlineParentState::ParentMissing}, worth reporting to the editor) and from an inline
 * child whose parent table cannot be translated at all
 * ({@see InlineParentState::ParentNotTranslatable}, silent, localized on its own).
 */
final class InlineParentResolution
{
    private function __construct(
        public readonly InlineParentState $state,
        public readonly ?InlineParentReference $reference = null,
    ) {
    }

    public static function resolved(InlineParentReference $reference): self
    {
        return new self(InlineParentState::Resolved, $reference);
    }

    public static function notInlineChild(): self
    {
        return new self(InlineParentState::NotInlineChild);
    }

    public static function ambiguous(): self
    {
        return new self(InlineParentState::Ambiguous);
    }

    public static function parentMissing(): self
    {
        return new self(InlineParentState::ParentMissing);
    }

    public static function parentNotTranslatable(): self
    {
        return new self(InlineParentState::ParentNotTranslatable);
    }

    public function isResolved(): bool
    {
        return $this->state === InlineParentState::Resolved;
    }
}
