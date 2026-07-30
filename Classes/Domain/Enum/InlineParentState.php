<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Domain\Enum;

/**
 * Outcome of resolving the connected mode inline (IRRE) parent of a record, see
 * {@see \WebVision\Deepltranslate\Core\Service\InlineRelationResolver::resolveParentReference()}.
 */
enum InlineParentState
{
    /**
     * The record is not an inline child in connected mode - the common case for any standalone
     * record, for example a `tt_content` element placed directly on a page. Must be localized
     * normally and must not be reported to the editor.
     */
    case NotInlineChild;

    /**
     * The record could be an inline child of more than one parent at the same time, which cannot be
     * resolved reliably. Points at a broken or conflicting TCA relation configuration and should be
     * reported to the editor.
     */
    case Ambiguous;

    /**
     * The record points at a parent record through its `foreign_field`, but that parent record does
     * not exist (any more). Points at broken data and should be reported to the editor.
     */
    case ParentMissing;

    /**
     * The record is an inline child in connected mode and its parent was resolved unambiguously.
     */
    /**
     * The record is an inline child in connected mode, but the parent table itself is not
     * translatable - most prominently `sys_file_metadata`, which is owned by the
     * non-translatable `sys_file`. There can never be a parent localization to attach the
     * child to, so the child has to be localized on its own instead of being handed over to
     * the DataHandler command dealing with inline children.
     */
    case ParentNotTranslatable;

    case Resolved;
}
