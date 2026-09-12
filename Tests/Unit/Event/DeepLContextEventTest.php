<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WebVision\Deepltranslate\Core\Domain\Dto\CurrentPage;
use WebVision\Deepltranslate\Core\Event\DeepLContextEvent;

#[CoversClass(DeepLContextEvent::class)]
final class DeepLContextEventTest extends UnitTestCase
{
    #[Test]
    public function constructorStoresProvidedValues(): void
    {
        $currentPage = new CurrentPage(42, 'Test page');
        $event = new DeepLContextEvent('initial context', 'DE', 'EN-GB', $currentPage);

        $this->assertSame('initial context', $event->context);
        $this->assertSame('DE', $event->sourceLanguage);
        $this->assertSame('EN-GB', $event->targetLanguage);
        $this->assertSame($currentPage, $event->currentPage);
    }

    #[Test]
    public function contextCanBeOverriddenByEventListener(): void
    {
        $event = new DeepLContextEvent('site context', 'DE', 'EN-GB', null);

        $event->context = 'enriched by a listener';

        $this->assertSame('enriched by a listener', $event->context);
    }

    #[Test]
    public function contextCanBeDisabledByEmptyString(): void
    {
        $event = new DeepLContextEvent('site context', 'DE', 'EN-GB', null);

        $event->context = '';

        $this->assertSame('', $event->context);
    }
}