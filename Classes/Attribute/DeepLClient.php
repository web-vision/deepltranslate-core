<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class DeepLClient
{
    public const TAG_NAME = 'deepl.client';
}
