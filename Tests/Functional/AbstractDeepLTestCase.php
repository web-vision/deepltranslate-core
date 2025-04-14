<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional;

use Closure;
use DeepL\Translator;
use DeepL\TranslatorOptions;
use Exception;
use phpmock\phpunit\PHPMock;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\StringUtility;
use WebVision\Deepltranslate\Core\Client;
use WebVision\Deepltranslate\Core\ClientInterface;
use WebVision\Deepltranslate\Core\ConfigurationInterface;

abstract class AbstractDeepLTestCase extends FunctionalTestCase
{
    use PHPMock;

    /**
     * @var string
     */
    protected $authKey = 'mock_server';

    /**
     * @var string|false
     */
    protected $serverUrl = false;

    /**
     * @var string|false
     */
    protected $proxyUrl = false;

    protected bool $isMockServer = false;

    protected bool $isMockProxyServer = false;

    protected ?string $sessionNoResponse = null;

    protected ?string $session429Count = null;
    protected ?string $sessionInitCharacterLimit = null;

    protected ?string $sessionInitDocumentLimit = null;

    protected ?string $sessionInitTeamDocumentLimit = null;

    protected ?string $sessionDocFailure = null;

    protected ?int $sessionDocQueueTime = null;

    protected ?int $sessionDocTranslateTime = null;

    protected ?bool $sessionExpectProxy = null;

    /**
     * @var array<non-empty-string, non-empty-string>
     */
    protected const EXAMPLE_TEXT = [
        'bg' => 'протонен лъч',
        'cs' => 'protonový paprsek',
        'da' => 'protonstråle',
        'de' => 'Protonenstrahl',
        'el' => 'δέσμη πρωτονίων',
        'en' => 'proton beam',
        'en-US' => 'proton beam',
        'en-GB' => 'proton beam',
        'es' => 'haz de protones',
        'et' => 'prootonikiirgus',
        'fi' => 'protonisäde',
        'fr' => 'faisceau de protons',
        'hu' => 'protonnyaláb',
        'id' => 'berkas proton',
        'it' => 'fascio di protoni',
        'ja' => '陽子ビーム',
        'ko' => '양성자 빔',
        'lt' => 'protonų spindulys',
        'lv' => 'protonu staru kūlis',
        'nb' => 'protonstråle',
        'nl' => 'protonenbundel',
        'pl' => 'wiązka protonów',
        'pt' => 'feixe de prótons',
        'pt-BR' => 'feixe de prótons',
        'pt-PT' => 'feixe de prótons',
        'ro' => 'fascicul de protoni',
        'ru' => 'протонный луч',
        'sk' => 'protónový lúč',
        'sl' => 'protonski žarek',
        'sv' => 'protonstråle',
        'tr' => 'proton ışını',
        'uk' => 'протонний пучок',
        'zh' => '质子束',
    ];

    /**
     * @var non-empty-string[]
     */
    protected array $coreExtensionsToLoad = [
        'typo3/cms-setup',
    ];

    /**
     * @var non-empty-string[]
     */
    protected array $testExtensionsToLoad = [
        'web-vision/deepltranslate-core',
        __DIR__ . '/Fixtures/Extensions/test_services_override',
    ];

    protected const EXAMPLE_DOCUMENT_INPUT = AbstractDeepLTestCase::EXAMPLE_TEXT['en'];

    protected const EXAMPLE_DOCUMENT_OUTPUT = AbstractDeepLTestCase::EXAMPLE_TEXT['de'];

    protected string $EXAMPLE_LARGE_DOCUMENT_INPUT = '';

    protected string $EXAMPLE_LARGE_DOCUMENT_OUTPUT = '';

    protected function setUp(): void
    {
        if ((new Typo3Version())->getMajorVersion() >= 13) {
            $this->coreExtensionsToLoad[] = 'typo3/cms-install';
        }
        $this->EXAMPLE_LARGE_DOCUMENT_INPUT = str_repeat(AbstractDeepLTestCase::EXAMPLE_TEXT['en'] . PHP_EOL, 1000);
        $this->EXAMPLE_LARGE_DOCUMENT_OUTPUT = str_repeat(AbstractDeepLTestCase::EXAMPLE_TEXT['de'] . PHP_EOL, 1000);
        $this->serverUrl = getenv('DEEPL_SERVER_URL');
        $this->proxyUrl = getenv('DEEPL_PROXY_URL');
        $this->isMockServer = getenv('DEEPL_MOCK_SERVER_PORT') !== false;
        $this->isMockProxyServer = $this->isMockServer && getenv('DEEPL_MOCK_PROXY_SERVER_PORT') !== false;

        if ($this->isMockServer) {
            $this->authKey = 'mock_server';
            if ($this->serverUrl === false) {
                throw new RuntimeException(
                    'DEEPL_SERVER_URL environment variable must be set if using a mock server',
                    1733938285,
                );
            }
        } else {
            if (getenv('DEEPL_AUTH_KEY') === false) {
                throw new RuntimeException(
                    'DEEPL_AUTH_KEY environment variable must be set unless using a mock server',
                    1733938290,
                );
            }
            $this->authKey = getenv('DEEPL_AUTH_KEY');
        }
        parent::setUp();
        $this->instantiateMockServerClient();
    }

    private function makeSessionName(): string
    {
        return sprintf('%s/%s', self::getInstanceIdentifier(), StringUtility::getUniqueId());
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionHeaders(): array
    {
        $headers = [];
        if ($this->sessionNoResponse !== null) {
            $headers['mock-server-session-no-response-count'] = (string)($this->sessionNoResponse);
        }
        if ($this->session429Count !== null) {
            $headers['mock-server-session-429-count'] = (string)($this->session429Count);
        }
        if ($this->sessionInitCharacterLimit !== null) {
            $headers['mock-server-session-init-character-limit'] = (string)($this->sessionInitCharacterLimit);
        }
        if ($this->sessionInitDocumentLimit !== null) {
            $headers['mock-server-session-init-document-limit'] = (string)($this->sessionInitDocumentLimit);
        }
        if ($this->sessionInitTeamDocumentLimit !== null) {
            $headers['mock-server-session-init-team-document-limit'] = (string)($this->sessionInitTeamDocumentLimit);
        }
        if ($this->sessionDocFailure !== null) {
            $headers['mock-server-session-doc-failure'] = (string)($this->sessionDocFailure);
        }
        if ($this->sessionDocQueueTime !== null) {
            $headers['mock-server-session-doc-queue-time'] = (string)($this->sessionDocQueueTime * 1000);
        }
        if ($this->sessionDocTranslateTime !== null) {
            $headers['mock-server-session-doc-translate-time'] = (string)($this->sessionDocTranslateTime * 1000);
        }
        if ($this->sessionExpectProxy !== null) {
            $headers['mock-server-session-expect-proxy'] = $this->sessionExpectProxy ? '1' : '0';
        }
        $headers['mock-server-session'] = $this->makeSessionName();
        return $headers;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function instantiateMockServerClient(array $options = []): void
    {
        $mergedOptions = array_replace(
            [TranslatorOptions::HEADERS => $this->sessionHeaders()],
            $options
        );
        if ($this->serverUrl !== false) {
            $mergedOptions[TranslatorOptions::SERVER_URL] = $this->serverUrl;
        }
        $mockConfiguration = $this
            ->getMockBuilder(ConfigurationInterface::class)
            ->getMock();
        $mockConfiguration
            ->method('getApiKey')
            ->willReturn(self::getInstanceIdentifier());

        $client = new Client($mockConfiguration);
        $client->setLogger(new NullLogger());

        // use closure to set private option for translation
        $translator = new Translator(self::getInstanceIdentifier(), $mergedOptions);
        Closure::bind(
            function (Translator $translator) {
                $this->translator = $translator;
            },
            $client,
            Client::class
        )->call($client, $translator);

        /** @var Container $container */
        $container = $this->getContainer();
        $container->set(ClientInterface::class, $client);
    }

    public static function readFile(string $filepath): string
    {
        $size = filesize($filepath);
        if ($size == 0) {
            return '';
        }
        $fh = fopen($filepath, 'r');
        $size = filesize($filepath);
        $content = '';
        if ($fh !== false && $size !== false) {
            $content = fread($fh, $size);
            fclose($fh);
        }
        return $content ?: '';
    }

    public static function writeFile(string $filepath, string $content): void
    {
        $fh = fopen($filepath, 'w');
        if ($fh !== false) {
            fwrite($fh, $content);
            fclose($fh);
        }
    }

    /**
     * @return string[]
     */
    public function tempFiles(): array
    {
        $tempDir = sys_get_temp_dir() . '/deepl-php-test-' . Uuid::uuid4() . '/';
        $exampleDocument = $tempDir . 'example_document.txt';
        $exampleLargeDocument = $tempDir . 'example_large_document.txt';
        $outputDocumentPath = $tempDir . 'output_document.txt';

        mkdir($tempDir);
        $this->writeFile($exampleDocument, AbstractDeepLTestCase::EXAMPLE_DOCUMENT_INPUT);
        $this->writeFile($exampleLargeDocument, $this->EXAMPLE_LARGE_DOCUMENT_INPUT);

        return [$tempDir, $exampleDocument, $exampleLargeDocument, $outputDocumentPath];
    }

    public function assertExceptionContains(string $needle, callable $function): Exception
    {
        try {
            $function();
        } catch (Exception $exception) {
            static::assertStringContainsString($needle, $exception->getMessage());
            return $exception;
        }
        static::fail("Expected exception containing '$needle' but nothing was thrown");
    }

    /**
     * @param class-string $class
     */
    public function assertExceptionClass(string $class, callable $function): Exception
    {
        try {
            $function();
        } catch (Exception $exception) {
            static::assertEquals($class, get_class($exception));
            return $exception;
        }
        static::fail("Expected exception of class '$class' but nothing was thrown");
    }

    /**
     * This is necessary due to https://github.com/php-mock/php-mock-phpunit#restrictions
     * In short, as these methods can be called by other tests before UserAgentTest and other
     * tests that use their mocks are executed, we need to call `defineFunctionMock` before
     * calling the unmocked function, or the mock will not work.
     * Otherwise the tests will fail with:
     *     Expectation failed for method name is "delegate" when invoked 1 time(s).
     *     Method was expected to be called 1 times, actually called 0 times.
     */
    public static function setUpBeforeClass(): void
    {
        self::defineFunctionMock(__NAMESPACE__, 'curl_exec');
        self::defineFunctionMock(__NAMESPACE__, 'curl_getinfo');
        self::defineFunctionMock(__NAMESPACE__, 'curl_setopt_array');
    }
}
