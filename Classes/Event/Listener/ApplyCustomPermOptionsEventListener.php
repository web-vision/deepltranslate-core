<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Event\Listener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Core\Event\BootCompletedEvent;
use WebVision\Deepltranslate\Core\Access\AccessRegistry;

/**
 * Listen to {@see BootCompletedEvent} to apply all registered deepltranslate `customPermOptions`
 * gathered through {@see AccessRegistry::addAccess()} calls in extension `ext_localconf.php` files.
 */
final class ApplyCustomPermOptionsEventListener
{
    public function __construct(
        private AccessRegistry $accessRegistry,
    ) {}

    #[AsEventListener(identifier: 'deepltranslate-core/apply-custom-perm-options-deepltranslate')]
    public function __invoke(BootCompletedEvent $event): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions']['deepltranslate'] ??= [];
        $GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions']['deepltranslate']['header'] = 'Deepl Translate Access';
        foreach ($this->accessRegistry->getItems() as $access) {
            $GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions']['deepltranslate']['items'][$access->getIdentifier()] = [
                $access->getTitle(),
                $access->getIconIdentifier(),
                $access->getDescription(),
            ];
        }
    }
}
