<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Controller\Backend;

use Psr\Http\Message\ServerRequestInterface;

use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Core\Http\JsonResponse;
use WebVision\Deepltranslate\Core\Configuration;

/**
 * Controller for fetching the DeepL settings via Ajax route for usage in JavaScript inside the backend
 *
 * Configured backend ajax route: deepl_check_configuration
 *
 * @internal No public API
 */
#[Controller]
final class AjaxController
{
    private Configuration $configuration;

    public function __construct(
        Configuration $configuration
    ) {
        $this->configuration = $configuration;
    }

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
