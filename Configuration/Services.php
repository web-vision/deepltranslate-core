<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Dashboard\WidgetRegistry;
use WebVision\Deepltranslate\Core\Attribute\DeepLClient;
use WebVision\Deepltranslate\Core\Service\UsageService;
use WebVision\Deepltranslate\Core\Widgets\UsageWidget;

return function (ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder) {
    $typo3Version = new Typo3Version();
    $majorVersion = $typo3Version->getMajorVersion();
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

    //==================================================================================================================
    // Define the location of the PHP sources of our extension.
    // In addition, exclude Extbase models that should never be used via DI.
    //==================================================================================================================
    $services->load(
        sprintf('WebVision\\Deepltranslate\\Core\\Core%s\\', $majorVersion),
        sprintf(__DIR__ . '/../Core%s/', $majorVersion),
    );
    //==================================================================================================================

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

    $containerBuilder->registerAttributeForAutoconfiguration(
        DeepLClient::class,
        static function (ChildDefinition $definition, DeepLClient $attribute): void {}
    );
    // Ensure that Process::TAG_NAME processes are set as public services
    $containerBuilder->addCompilerPass(new PublicServicePass(DeepLClient::TAG_NAME));
};
