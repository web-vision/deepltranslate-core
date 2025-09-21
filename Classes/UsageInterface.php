<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core;

use DeepL\Usage;
use WebVision\Deepltranslate\Core\Exception\ApiKeyNotSetException;

/**
 * Describes client implementation able to provide Usage statistics, for example fetched using the DeepL API.
 */
interface UsageInterface extends ClientInterface
{
    /**
     * @throws ApiKeyNotSetException
     */
    public function getUsage(): ?Usage;
}
