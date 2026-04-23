<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Access;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WebVision\Deepltranslate\Core\Access\AccessItemInterface;
use WebVision\Deepltranslate\Core\Access\AccessRegistry;

#[CoversClass(AccessRegistry::class)]
final class AccessRegistryTest extends UnitTestCase
{
    #[Test]
    public function hasReturnsFalseIfIdentifierDoesNotExists(): void
    {
        $this->assertFalse((new AccessRegistry([]))->has('not-existing-identifier'));
    }

    #[Test]
    public function hasReturnsTrueIfIdentifierExists(): void
    {
        $identifier = 'mock-identifier';
        $accessItemMock = $this->createMock(AccessItemInterface::class);
        $accessItemMock->expects($this->exactly(1))->method('getIdentifier')->willReturn($identifier);
        $this->assertTrue((new AccessRegistry([$accessItemMock]))->has($identifier));
    }

    #[Test]
    public function getReturnsNullIfIdentifierDoesNotExists(): void
    {
        $this->assertNull((new AccessRegistry([]))->get('not-existing-identifier'));
    }

    #[Test]
    public function getReturnsExpectedExistingAccessItem(): void
    {
        $accessItemMock = $this->createMock(AccessItemInterface::class);
        $accessItemMock->expects($this->exactly(1))->method('getIdentifier')->willReturn('mock-identifier');
        $this->assertSame($accessItemMock, (new AccessRegistry([$accessItemMock]))->get('mock-identifier'));
    }

    #[IgnoreDeprecations]
    #[Test]
    public function addAccessTriggersDeprecationAndDoesNotAddItem(): void
    {
        $accessRegistry = new AccessRegistry([]);
        $identifier = 'testIdentifier';

        $accessItemMock = $this->createMock(AccessItemInterface::class);
        $accessItemMock->expects($this->never())->method('getIdentifier')->willReturn($identifier);

        $this->expectUserDeprecationMessage(
            '"WebVision\Deepltranslate\Core\Access\AccessRegistry::addAccess" is deprecated '
            . 'and can be removed and are added by the dependency injection container based on '
            . '"WebVision\Deepltranslate\Core\Access\AccessItemInterface" automatically.'
        );

        $accessRegistry->addAccess($accessItemMock);
        $this->assertSame([], (new \ReflectionProperty($accessRegistry, 'items'))->getValue($accessRegistry));
    }

    #[IgnoreDeprecations]
    #[Test]
    public function getAccessReturnsNullForNonExistentIdentifierAndTriggersExpectedDeprecation(): void
    {
        $this->expectUserDeprecationMessage(
            '"WebVision\Deepltranslate\Core\Access\AccessRegistry::getAccess" is deprecated. '
            . 'Use "WebVision\Deepltranslate\Core\Access\AccessRegistry::get()" instead.'
        );

        $this->assertNull((new AccessRegistry([]))->getAccess('nonExistentIdentifier'));
    }

    #[IgnoreDeprecations]
    #[Test]
    public function getAccessReturnsExpectedItemAndTriggersExpectedDeprecation(): void
    {
        $identifier = 'testIdentifier';
        $accessItemMock = $this->createMock(AccessItemInterface::class);
        $accessItemMock->expects($this->exactly(1))->method('getIdentifier')->willReturn($identifier);

        $this->expectUserDeprecationMessage(
            '"WebVision\Deepltranslate\Core\Access\AccessRegistry::getAccess" is deprecated. '
            . 'Use "WebVision\Deepltranslate\Core\Access\AccessRegistry::get()" instead.'
        );

        $this->assertSame($accessItemMock, (new AccessRegistry([$accessItemMock]))->getAccess($identifier));
    }

    #[IgnoreDeprecations]
    #[Test]
    public function hasAccessReturnsFalseForNonExistingIdentifierAndTriggersDeprecation(): void
    {
        $identifier = 'nonExistingIdentifier';

        $this->expectUserDeprecationMessage(
            '"AccessRegistry::hasAccess()" is deprecated. Use "AccessRegistry::has()" instead.'
        );

        $this->assertFalse((new AccessRegistry([]))->hasAccess($identifier));
    }

    #[IgnoreDeprecations]
    #[Test]
    public function hasAccessReturnsTrueForExistingIdentifierAndTriggersDeprecation(): void
    {
        $identifier = 'existingIdentifier';
        $accessItemMock = $this->createMock(AccessItemInterface::class);
        $accessItemMock->expects($this->exactly(1))->method('getIdentifier')->willReturn($identifier);

        $this->expectUserDeprecationMessage(
            '"AccessRegistry::hasAccess()" is deprecated. Use "AccessRegistry::has()" instead.'
        );

        $this->assertTrue((new AccessRegistry([$accessItemMock]))->hasAccess($identifier));
    }

    #[IgnoreDeprecations]
    #[Test]
    public function getAllAccessReturnsEmptyArrayAndTriggersExpectedDeprecation(): void
    {
        $this->expectUserDeprecationMessage(
            '"WebVision\Deepltranslate\Core\Access\AccessRegistry::getAllAccess" is deprecated. '
            . 'Use "WebVision\Deepltranslate\Core\Access\AccessRegistry::getItems()" instead.'
        );

        $this->assertSame([], (new AccessRegistry([]))->getAllAccess());
    }

    #[IgnoreDeprecations]
    #[Test]
    public function getAllAccessReturnsExpectedItemsAndTriggersExpectedDeprecation(): void
    {
        $identifier1 = 'testIdentifier1';
        $accessItemMock1 = $this->createMock(AccessItemInterface::class);
        $accessItemMock1->expects($this->exactly(1))->method('getIdentifier')->willReturn($identifier1);
        $identifier2 = 'testIdentifier2';
        $accessItemMock2 = $this->createMock(AccessItemInterface::class);
        $accessItemMock2->expects($this->exactly(1))->method('getIdentifier')->willReturn($identifier2);
        $expected = [
            $identifier2 => $accessItemMock2,
            $identifier1 => $accessItemMock1,
        ];
        $this->expectUserDeprecationMessage(
            '"WebVision\Deepltranslate\Core\Access\AccessRegistry::getAllAccess" is deprecated. '
            . 'Use "WebVision\Deepltranslate\Core\Access\AccessRegistry::getItems()" instead.'
        );

        $this->assertSame($expected, (new AccessRegistry(array_values($expected)))->getAllAccess());
    }
}
