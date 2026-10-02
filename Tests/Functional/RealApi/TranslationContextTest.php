<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\RealApi;

use GuzzleHttp\Middleware;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;
use WebVision\Deepltranslate\Core\Domain\Dto\TranslateContext;
use WebVision\Deepltranslate\Core\Domain\Enum\ContentFormat;
use WebVision\Deepltranslate\Core\Service\DeeplService;
use WebVision\Deepltranslate\Core\Service\ProcessingInstruction;

/**
 * Issue #666: sends the same ambiguous content with different contexts to the real DeepL API, together with the
 * options the extension sends rich text with, and checks that the context decides the meaning and is not billed.
 * The words were chosen on 2026-10-02: without a context DeepL translated each of them in another meaning.
 *
 * Billed per character like {@see RichTextTagHandlingTest}, run it the same way:
 *
 * ```
 * read -rs DEEPL_AUTH_KEY && export DEEPL_AUTH_KEY
 * Build/Scripts/runTests.sh -s functionalDeepLApi
 * ```
 */
#[Group('deepl-real-api')]
final class TranslationContextTest extends FunctionalTestCase
{
    /**
     * @var non-empty-string[]
     */
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
    ];

    /**
     * @var non-empty-string[]
     */
    protected array $testExtensionsToLoad = [
        'web-vision/deepl-base',
        'web-vision/deeplcom-deepl-php',
        'web-vision/deepltranslate-core',
    ];

    /**
     * @var \ArrayObject<int, array{request: RequestInterface, response: ResponseInterface|null, error: mixed, options: array<mixed>}>
     */
    private \ArrayObject $exchanges;

    protected function setUp(): void
    {
        $authKey = (string)getenv('DEEPL_AUTH_KEY');
        if ($authKey === '' || getenv('DEEPL_MOCK_SERVER_PORT') !== false) {
            $this->markTestSkipped('Requires a real DeepL API key in DEEPL_AUTH_KEY and no DeepL mock server.');
        }
        parent::setUp();
        // Set at runtime only, so the key is never written into the settings file of the test instance.
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['deepltranslate_core']['apiKey'] = $authKey;
        $this->exchanges = new \ArrayObject();
        $exchanges = $this->exchanges;
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['deepl-real-api-history'] = Middleware::history($exchanges);
        unset($GLOBALS['DEEPL_TESTING']);
        $this->get(ProcessingInstruction::class)->setProcessingInstruction(null, null, true);
    }

    /**
     * Only the translation with a context is checked against an expectation, the one without is only compared to it.
     */
    public static function contextDataProvider(): \Generator
    {
        yield 'court of a tennis club is a tennis court' => [
            'content' => '<p>The court is closed on Sundays.</p>',
            'context' => 'This is the website of a tennis club. Members book tennis courts online.',
            'expectedPattern' => '/Platz/',
        ];
        yield 'court of a law firm is a court of law' => [
            'content' => '<h2>Book a court</h2>',
            'context' => 'The page of a law firm in Berlin. We represent our clients before the courts and help them with appointments at court.',
            'expectedPattern' => '/Gericht/',
        ];
        yield 'pitch of a football club is a playing field' => [
            'content' => '<h2>Our pitch</h2>',
            'context' => 'Website of an amateur football club. The club ground has two grass fields.',
            'expectedPattern' => '/Spielfeld|Platz/',
        ];
    }

    #[Test]
    #[DataProvider('contextDataProvider')]
    public function contextDecidesTheMeaningOfAmbiguousWords(string $content, string $context, string $expectedPattern): void
    {
        $withoutContext = $this->translate($content, '');
        $withContext = $this->translate($content, $context);

        $message = sprintf("Without context:\n%s\nWith context:\n%s", $withoutContext, $withContext);
        $this->assertMatchesRegularExpression($expectedPattern, $withContext, $message);
        $this->assertNotSame($withoutContext, $withContext, $message);
        $requests = array_map(
            static fn(array $exchange): mixed => json_decode((string)$exchange['request']->getBody(), true),
            $this->exchanges->getArrayCopy()
        );
        $this->assertCount(2, $requests);
        $this->assertIsArray($requests[0]);
        $this->assertIsArray($requests[1]);
        $this->assertArrayNotHasKey('context', $requests[0]);
        $this->assertSame($context, $requests[1]['context'] ?? null);
    }

    #[Test]
    public function charactersOfTheContextAreNotBilled(): void
    {
        $content = '<p>The court is closed on Sundays.</p>';
        $this->translate($content, '');
        $this->translate($content, 'This is the website of a tennis club. Members book tennis courts online.');

        $billedCharacters = array_map(
            fn(array $exchange): int => array_sum(array_column(
                (array)(json_decode($this->bodyOf($exchange['response']), true)['translations'] ?? []),
                'billed_characters'
            )),
            $this->exchanges->getArrayCopy()
        );
        $this->assertCount(2, $billedCharacters);
        $this->assertGreaterThan(0, $billedCharacters[0]);
        $this->assertSame($billedCharacters[0], $billedCharacters[1]);
    }

    private function translate(string $content, string $context): string
    {
        $translateContext = new TranslateContext($content);
        $translateContext->setSourceLanguageCode('EN');
        $translateContext->setTargetLanguageCode('DE');
        $translateContext->setContentFormat(ContentFormat::RichText);
        $translateContext->setContext($context);
        $translated = $this->get(DeeplService::class)->translateContent($translateContext);
        $this->assertNotSame('', $translated, 'DeepL did not answer');
        return $translated;
    }

    private function bodyOf(?ResponseInterface $response): string
    {
        if ($response === null) {
            return '';
        }
        $body = $response->getBody();
        $body->rewind();
        return $body->getContents();
    }
}
