<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Event\Listener;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Controller\Event\RenderAdditionalContentToRecordListEvent;
use TYPO3\CMS\Core\Site\Entity\Site;
use WebVision\Deepltranslate\Core\Event\RenderLocalizationSelectAllowed;
use WebVision\Deepltranslate\Core\Form\TranslationDropdownGenerator;

final class RenderLocalizationSelect
{
    public function __construct(
        private readonly TranslationDropdownGenerator $generator,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function __invoke(RenderAdditionalContentToRecordListEvent $event): void
    {
        $request = $event->getRequest();
        // Check, if some event listener doesn't allow rendering here.
        // For use cases see Event
        $renderingAllowedEvent = $this->eventDispatcher->dispatch(new RenderLocalizationSelectAllowed($request));
        if ($renderingAllowedEvent->renderingAllowed === false) {
            return;
        }
        /** @var Site $site */
        $site = $request->getAttribute('site');
        $siteLanguages = $site->getLanguages();
        $options = $this->generator->buildTranslateDropdownOptions($siteLanguages, (int)($request->getQueryParams()['id'] ?? 0), $request->getUri());
        if ($options !== '') {
            $additionalHeader = '<div class="form-row">'
                . '<div class="form-group">'
                . '<select class="form-select" name="createNewLanguage" data-global-event="change" data-action-navigate="$value">'
                . $options
                . '</select>'
                . '</div>'
                . '</div>';
            $event->addContentAbove($additionalHeader);
        }
    }
}
