<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Hooks;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Hooks\TranslateHook;

#[CoversClass(TranslateHook::class)]
final class LostLinkFlashMessageTest extends AbstractLostLinkTestCase
{
    #[Test]
    public function dataHandlerTranslationKeepsTheTextAndWarnsAboutTheLostLink(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tt_content' => [10 => ['deepltranslate' => 2]]]);
        $dataHandler->process_cmdmap();

        $this->assertSame([], $dataHandler->errorLog);
        $this->assertSame(
            '<p>Get the warranty extension for your bike on the service page.</p>',
            $this->fetchTranslation()['bodytext'] ?? null
        );
        $this->assertLostLinkMessage();
    }
}
