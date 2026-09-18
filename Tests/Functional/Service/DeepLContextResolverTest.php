<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use WebVision\Deepltranslate\Core\Service\DeepLContextResolver;

#[CoversClass(DeepLContextResolver::class)]
final class DeepLContextResolverTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'install',
        'web-vision/deepltranslate-core',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages_context.csv');
    }

    #[Test]
    public function contextCanBeResolvedFromContainer(): void
    {
        $resolver = GeneralUtility::makeInstance(DeepLContextResolver::class);

        $this->assertInstanceOf(DeepLContextResolver::class, $resolver);
    }

    #[Test]
    #[DataProvider('cascadeContextsAreResolvedDataProvider')]
    public function cascadeContextsAreResolved(int $pageId, string $siteContext, string $expected): void
    {
        $resolver = GeneralUtility::makeInstance(DeepLContextResolver::class);

        self::assertSame($expected, $resolver->resolve($pageId, $siteContext));
    }

    public static function cascadeContextsAreResolvedDataProvider(): iterable
    {
        yield 'page context takes precedence over site context' => [
            1,
            'site context',
            'Context from page',
        ];
        yield 'empty page context falls back to site context' => [
            2,
            'site context',
            'site context',
        ];
        yield 'empty page and site context result in empty context' => [
            3,
            '',
            '',
        ];
        yield 'unknown page falls back to site context' => [
            99,
            'site context',
            'site context',
        ];
        yield 'zero page id falls back to site context' => [
            0,
            'site context',
            'site context',
        ];
    }
}