<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Access;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WebVision\Deepltranslate\Core\Access\AccessItemInterface;
use WebVision\Deepltranslate\Core\Access\AccessRegistry;
use WebVision\Deepltranslate\Core\Access\AllowedTranslateAccess;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

final class AccessRegistryTest extends AbstractDeepLTestCase
{
    /**
     * @return AllowedTranslateAccess[]
     */
    private static function getAllDefaultAccessItemsInExpectedOrder(): array
    {
        return [
            new AllowedTranslateAccess(),
        ];
    }

    public static function deepltranslateCoreDefaultAccessItems(): \Generator
    {
        $items = self::getAllDefaultAccessItemsInExpectedOrder();
        foreach ($items as $item) {
            yield $item->getIdentifier() => [
                'identifier' => $item->getIdentifier(),
                'item' => $item,
            ];
        }
    }

    #[DataProvider('deepltranslateCoreDefaultAccessItems')]
    #[Test]
    public function hasExpectedAccessItem(string $identifier, AccessItemInterface $item): void
    {
        $this->assertTrue($this->get(AccessRegistry::class)->has($identifier));
    }

    #[DataProvider('deepltranslateCoreDefaultAccessItems')]
    #[Test]
    public function getReturnsExpectedAccessItem(string $identifier, AccessItemInterface $item): void
    {
        $this->assertSame($identifier, $this->get(AccessRegistry::class)->get($identifier)?->getIdentifier());
    }

    public static function verifyAllDefaultAccessItemsAreRegisteredDataSets(): \Generator
    {
        yield 'EXT:deepltranslate_core' => [
            'expected' => array_map(fn(AccessItemInterface $item) => $item->getIdentifier(), array_values(self::getAllDefaultAccessItemsInExpectedOrder())),
        ];
    }

    /**
     * @param string[] $expected
     */
    #[DataProvider('verifyAllDefaultAccessItemsAreRegisteredDataSets')]
    #[Test]
    public function verifyAllDefaultAccessItemsAreRegistered(array $expected): void
    {
        $this->assertSame($expected, array_map(fn(AccessItemInterface $item) => $item->getIdentifier(), array_values($this->get(AccessRegistry::class)->getItems())));
    }
}
