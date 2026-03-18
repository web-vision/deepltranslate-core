<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Access;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WebVision\Deepltranslate\Core\Access\AccessItemInterface;
use WebVision\Deepltranslate\Core\Access\AccessRegistry;

#[CoversClass(AccessRegistry::class)]
class AccessRegistryTest extends UnitTestCase
{
    #[Test]
    public function registerAccessStoresTheAccessCorrectly(): void
    {
        $accessRegistry = new AccessRegistry();

        $identifier = 'testIdentifier';

        $accessObject = $this->createMock(AccessItemInterface::class);
        $accessObject->expects($this->once())->method('getIdentifier')->willReturn($identifier);

        $accessRegistry->addAccess($accessObject);
        $object = $accessRegistry->getAccess($identifier);

        $this->assertSame($accessObject, $object);
    }

    #[Test]
    public function getAccessReturnsNullForNonExistentIdentifier(): void
    {
        $accessRegistry = new AccessRegistry();

        $this->assertNull($accessRegistry->getAccess('nonExistentIdentifier'));
    }
}
