<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Core14\Hooks;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Localization\LocalizationHandlerRegistry;
use TYPO3\CMS\Backend\Localization\LocalizationInstructions;
use TYPO3\CMS\Backend\Localization\LocalizationMode;
use WebVision\Deepltranslate\Core\Hooks\TranslateHook;
use WebVision\Deepltranslate\Core\Tests\Functional\Hooks\AbstractLostLinkTestCase;

/**
 * The localization wizard of TYPO3 v14 translates in an AJAX request. The warning is kept in the session of the
 * editor, the wizard reloads the backend afterwards.
 */
#[CoversClass(TranslateHook::class)]
#[Group('not-core-13')]
final class LostLinkInLocalizationWizardTest extends AbstractLostLinkTestCase
{
    #[Test]
    public function localizationWizardKeepsTheWarningInTheSession(): void
    {
        $handler = $this->get(LocalizationHandlerRegistry::class)->getHandler('deepltranslate');
        $handler->processLocalization(new LocalizationInstructions('tt_content', 10, 0, 2, LocalizationMode::TRANSLATE, []));

        $this->assertSame(
            '<p>Get the warranty extension for your bike on the service page.</p>',
            $this->fetchTranslation()['bodytext'] ?? null
        );
        $this->assertLostLinkMessage();
        $this->assertNotEmpty(
            $GLOBALS['BE_USER']->getSessionData('core.template.flashMessages'),
            'The warning must be stored in the session to survive the AJAX request.'
        );
    }
}
