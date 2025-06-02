<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\ViewHelpers;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\Cache\FluidCacheInterface;
use TYPO3Fluid\Fluid\Core\Cache\SimpleFileCache;

final class ExtensionActiveViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $coreExtensionsToLoad = [
        'typo3/cms-setup',
    ];

    protected array $testExtensionsToLoad = [
        'web-vision/deepl-base',
        'web-vision/deepltranslate-core',
    ];

    protected static FluidCacheInterface $cache;

    /**
     *  Absolute path to cache directory
     */
    protected static string $cachePath;

    public static function setUpBeforeClass(): void
    {
        self::$cachePath = sys_get_temp_dir() . '/' . 'fluid-functional-tests-' . sha1(__CLASS__);
        mkdir(self::$cachePath);
        self::$cache = (new SimpleFileCache(self::$cachePath));
    }

    public static function tearDownAfterClass(): void
    {
        self::$cache->flush();
        rmdir(self::$cachePath);
    }

    protected function setUp(): void
    {
        if ((new Typo3Version())->getMajorVersion() >= 13) {
            $this->coreExtensionsToLoad[] = 'typo3/cms-install';
        }
        parent::setUp();
    }

    public static function renderDataProvider(): Generator
    {
        yield 'extension name empty, await else' => [
            '<deepl:be.extensionActive extension="" then="thenArgument" else="elseArgument" />',
            [],
            'elseArgument',
        ];
        yield 'extension set to own, await then' => [
            '<deepl:be.extensionActive extension="deepltranslate_core" then="thenArgument" else="elseArgument" />',
            [],
            'thenArgument',
        ];

        yield 'extension set to non existent, await else' => [
            '<deepl:be.extensionActive extension="non_existent" then="thenArgument" else="elseArgument" />',
            [],
            'elseArgument',
        ];

        yield 'extension provided as undefined fluid variable placeholder, await else' => [
            '<deepl:be.extensionActive extension="{someUndefinedVariable}" then="thenArgument" else="elseArgument" />',
            [],
            'elseArgument',
        ];
    }

    /**
     * @param array<array-key, mixed> $variables
     */
    #[DataProvider('renderDataProvider')]
    #[Test]
    public function render(string $template, array $variables, string $expected): void
    {
        if ((new Typo3Version())->getMajorVersion() < 13) {
            $view = new TemplateView();
            $view->getRenderingContext()->getViewHelperResolver()->addNamespace('deepl', 'WebVision\\Deepltranslate\\Core\\ViewHelpers');
            $view->getRenderingContext()->setCache(self::$cache);
            $view->getRenderingContext()->getTemplatePaths()->setTemplateSource($template);
        } else {
            $view = GeneralUtility::makeInstance(ViewFactoryInterface::class)->create(new ViewFactoryData());
            $view->getRenderingContext()->getViewHelperResolver()->addNamespace('deepl', 'WebVision\\Deepltranslate\\Core\\ViewHelpers');
            $view->getRenderingContext()->setCache(self::$cache);
            $view->getRenderingContext()->getTemplatePaths()->setTemplateSource($template);
        }
        $view->assignMultiple($variables);
        static::assertSame($expected, $view->render());
    }
}
