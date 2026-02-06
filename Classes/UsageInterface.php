<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core;

use DeepL\Usage;
use WebVision\Deepltranslate\Core\Exception\ApiKeyNotSetException;

interface UsageInterface extends ClientInterface
{
    /**
     * @throws ApiKeyNotSetException
     */
    public function getUsage(): ?Usage;
}
