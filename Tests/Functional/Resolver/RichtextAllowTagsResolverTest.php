<?php

declare(strict_types=1);

namespace Functional\Resolver;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\WvDeepltranslate\Resolver\RichtextAllowTagsResolver;

class RichtextAllowTagsResolverTest extends FunctionalTestCase
{
    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/wv_deepltranslate',
    ];

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [
        'rte_ckeditor',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/Fixtures/Pages.xml');
    }

    /**
     * @test
     */
    public function findRteConfigurationAllowTagsByRecords(): void
    {
        $richtextAllowTagsResolver = GeneralUtility::makeInstance(RichtextAllowTagsResolver::class);
        $allowTags = $richtextAllowTagsResolver->resolve('tt_content', 1, 'bodytext');

        static::assertTrue((bool)array_search('em', $allowTags));

        $yamlLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
        $config = $yamlLoader->load('EXT:rte_ckeditor/Configuration/RTE/Processing.yaml');

        static::assertSame($config['processing']['allowTags'], $allowTags);
    }
}
