<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Form;

use WebVision\Deepltranslate\Core\Core13\Form\InlineLocalizeWithDeeplControlRenderer as Core13InlineLocalizeWithDeeplControlRenderer;
use WebVision\Deepltranslate\Core\Core14\Form\InlineLocalizeWithDeeplControlRenderer as Core14InlineLocalizeWithDeeplControlRenderer;

/**
 * Renders the "Localize with DeepL" control of an inline (IRRE) child in a translated parent record.
 *
 * TYPO3 v13 offers no localization wizard for records, so the control links the `deepltranslate`
 * DataHandler command behind a confirmation. TYPO3 v14 opens its localization wizard, which offers the
 * DeepL localization handler next to the core ones.
 *
 * {@see Core13InlineLocalizeWithDeeplControlRenderer}
 * {@see Core14InlineLocalizeWithDeeplControlRenderer}
 *
 * @internal No public API
 */
interface InlineLocalizeWithDeeplControlRendererInterface
{
    /**
     * @param array<string, mixed> $data FormEngine data of the inline child
     */
    public function render(array $data, string $table, int $uid, int $targetLanguageId): string;
}
