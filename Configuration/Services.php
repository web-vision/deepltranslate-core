<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Dashboard\WidgetRegistry;
use WebVision\Deepltranslate\Core\Service\UsageService;
use WebVision\Deepltranslate\Core\Widgets\UsageWidget;

return function (ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder) {
    $typo3Version = new Typo3Version();
    $services = $containerConfigurator->services();

    //==================================================================================================================
    // The default configuration: allow autowire and autoconfigure,
    // no need to make every class public.
    //==================================================================================================================
    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->private(); // "private" is the default and can safely be omitted
    //==================================================================================================================

    if ($typo3Version->getMajorVersion() === 13) {
        //==================================================================================================================
        // Define the location of the PHP sources of our extension.
        // In addition, exclude Extbase models that should never be used via DI.
        //==================================================================================================================
        $services->load('WebVision\\Deepltranslate\\Core\\Core13\\', __DIR__ . '/../Core13/');
        //==================================================================================================================
    }

    /**
     * Check if WidgetRegistry is defined, which means that EXT:dashboard is available.
     * Registration directly in Services.yaml will break without EXT:dashboard installed!
     */
    if ($containerBuilder->hasDefinition(WidgetRegistry::class)) {
        // @todo Needs to be checked and verified for TYPO3 v14 if adoptions are required.
        $services->set('widgets.deepltranslate.widget.useswidget')
            ->class(UsageWidget::class)
            ->arg('$backendViewFactory', new Reference(BackendViewFactory::class))
            ->arg('$usageService', new Reference(UsageService::class))
            ->arg('$options', [])
            ->tag('dashboard.widget', [
                'identifier' => 'widgets-deepl-uses',
                'groupNames' => 'deepl',
                'title' => 'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:widgets.deepltranslate.widget.useswidget.title',
                'description' => 'LLL:EXT:deepltranslate_core/Resources/Private/Language/locallang.xlf:widgets.deepltranslate.widget.useswidget.description',
                'iconIdentifier' => 'content-widget-list',
                'height' => 'small',
                'width' => 'small',
            ]);
    }
};
