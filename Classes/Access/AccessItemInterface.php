<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Access;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(name: AccessItemInterface::class)]
interface AccessItemInterface
{
    /**
     * Unique access identifier
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * The title of the access
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * A short description about the access
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * The icon identifier for this access
     *
     * @return string
     */
    public function getIconIdentifier(): string;
}
