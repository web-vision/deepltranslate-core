<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Controller\Backend;

use Psr\Http\Message\ServerRequestInterface;

use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Core\Http\JsonResponse;
use WebVision\Deepltranslate\Core\ConfigurationInterface;

/**
 * Controller for fetching the DeepL settings via Ajax route for usage in JavaScript inside the backend
 *
 * Configured backend ajax route: deepl_check_configuration
 *
 * @internal No public API
 */
#[AsController]
final readonly class AjaxController
{
    public function __construct(
        private ConfigurationInterface $configuration,
    ) {}

    /**
     * check deepl Settings (url,apikey).
     */
    public function checkExtensionConfiguration(ServerRequestInterface $request): JsonResponse
    {
        $configurationStatus = [
            'status' => true,
            'message' => '',
        ];
        if ($this->configuration->getApiKey() == null) {
            $configurationStatus['status'] = false;
            $configurationStatus['message'] = 'Deepl settings not enabled';
        }
        return new JsonResponse($configurationStatus);
    }
}
