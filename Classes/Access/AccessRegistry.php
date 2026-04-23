<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Access;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\DependencyInjection\Exception\AutowiringFailedException;

#[Autoconfigure(public: true)]
final class AccessRegistry implements ContainerInterface
{
    /**
     * @var array<string, AccessItemInterface>
     */
    private array $items = [];

    /**
     * @param iterable<AccessItemInterface> $items
     */
    public function __construct(
        #[AutowireIterator(tag: AccessItemInterface::class, excludeSelf: true)]
        iterable $items,
    ) {
        foreach ($items as $item) {
            if (!$item instanceof AccessItemInterface) {
                throw new AutowiringFailedException(
                    self::class,
                    sprintf(
                        'Item "%s" for `$items` must be instance of "%s".',
                        (is_object($item) ? $item::class : gettype($item)),
                        AccessItemInterface::class,
                    ),
                    1776942850,
                );
            }
            $this->items[$item->getIdentifier()] = $item;
        }
    }

    public function get(string $id): ?AccessItemInterface
    {
        return $this->items[$id] ?? null;
    }

    public function has(string $id): bool
    {
        return $this->get($id) instanceof AccessItemInterface;
    }

    /**
     * @return array<string, AccessItemInterface>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param AccessItemInterface $accessItem
     * @deprecated since `6.0.0` and will be removed in `7.0.0`.
     */
    public function addAccess(AccessItemInterface $accessItem): void
    {
        trigger_error(
            sprintf(
                '"%s" is deprecated and can be removed and are added by the '
                . 'dependency injection container based on "%s" automatically.',
                __METHOD__,
                AccessItemInterface::class,
            ),
            E_USER_DEPRECATED,
        );
    }

    /**
     * @deprecated since `6.0.0` and will be removed in `7.0.0`.
     * @todo web-vision/deepltranslate-core:>=7.0.0 remove breaking.
     */
    public function getAccess(string $identifier): ?AccessItemInterface
    {
        trigger_error(
            sprintf(
                '"%s" is deprecated. Use "%s" instead.',
                __METHOD__,
                self::class . '::get()'
            ),
            E_USER_DEPRECATED,
        );
        return $this->get($identifier);
    }

    /**
     * @deprecated since `6.0.0` and will be removed in `7.0.0`.
     * @todo web-vision/deepltranslate-core:>=7.0.0 remove breaking.
     */
    public function hasAccess(string $identifier): bool
    {
        trigger_error(
            sprintf(
                '"%s" is deprecated. Use "%s" instead.',
                'AccessRegistry::hasAccess()',
                'AccessRegistry::has()'
            ),
            E_USER_DEPRECATED,
        );
        return $this->has($identifier);
    }

    /**
     * @return array<string, AccessItemInterface>
     * @deprecated since `6.0.0` and will be removed in `7.0.0`.
     * @todo web-vision/deepltranslate-core:>=7.0.0 remove breaking.
     */
    public function getAllAccess(): array
    {
        trigger_error(
            sprintf(
                '"%s" is deprecated. Use "%s" instead.',
                __METHOD__,
                self::class . '::getItems()'
            ),
            E_USER_DEPRECATED,
        );
        return $this->getItems();
    }
}
