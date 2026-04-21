<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Client;

use DeepL\TextResult;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use WebVision\Deepltranslate\Core\Client\DeepLClientInterface;

final class DeepLClientTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'web-vision/deepl-base',
        'web-vision/deeplcom-deepl-php',
        'web-vision/deepltranslate-core',
    ];

    protected array $coreExtensionsToLoad = [
        'typo3/cms-setup',
        'typo3/cms-install',
    ];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'deepltranslate_core' => [
                'apiKey' => 'mock_server',
            ],
        ],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance['EXTENSIONS']['deepltranslate_core']['serverUrl'] = getenv('DEEPL_SERVER_URL');
        parent::setUp();
    }

    #[Test]
    public function clientInstantiationFullyDoneByDependencyInjection(): void
    {
        $client = $this->get(DeepLClientInterface::class);

        $this->assertInstanceOf(DeepLClientInterface::class, $client);
    }

    #[Test]
    public function mockServerIsReturningCorrectTranslation(): void
    {
        $client = $this->get(DeepLClientInterface::class);

        $translated = $client->translateText('proton beam', null, 'DE');
        $this->assertInstanceOf(TextResult::class, $translated);
        $this->assertSame('Protonenstrahl', (string)$translated);
    }
}
