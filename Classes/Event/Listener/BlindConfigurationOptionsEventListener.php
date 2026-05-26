<?php

namespace WebVision\Deepltranslate\Core\Event\Listener;

use TYPO3\CMS\Lowlevel\Event\ModifyBlindedConfigurationOptionsEvent;

/**
 * Listen to {@see ModifyBlindedConfigurationOptionsEvent} to hide sensitive data
 * in the configuration module.
 */
final class BlindConfigurationOptionsEventListener
{
    public function __invoke(ModifyBlindedConfigurationOptionsEvent $event): void
    {
        $options = $event->getBlindedConfigurationOptions();

        if ($event->getProviderIdentifier() === 'confVars') {
            $options['TYPO3_CONF_VARS']['EXTENSIONS']['deepltranslate_core']['apiKey'] = '******';
        }

        $event->setBlindedConfigurationOptions($options);
    }
}
