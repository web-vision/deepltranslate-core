<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Hooks;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\RequestInterface;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Domain\Dto\TranslateContext;
use WebVision\Deepltranslate\Core\Service\DeepLContextResolver;
use WebVision\Deepltranslate\Core\Service\DeeplService;
use WebVision\Deepltranslate\Core\Service\ProcessingInstruction;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

/**
 * Issue #666: the context of the page of a translated record, else of its site, is sent to DeepL with every request
 * of the record's fields. DeepL is replaced by a handler returning the texts unchanged and recording the requests.
 */
#[CoversClass(DeepLContextResolver::class)]
#[CoversClass(DeeplService::class)]
final class TranslationContextTest extends AbstractDeepLTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => [
            'id' => 0,
            'title' => 'English',
            'locale' => 'en_US.UTF-8',
            'iso' => 'en',
            'hrefLang' => 'en-US',
            'direction' => '',
            'custom' => [
                'deeplTargetLanguage' => '',
            ],
        ],
        'DE' => [
            'id' => 1,
            'title' => 'Deutsch',
            'locale' => 'de_DE',
            'iso' => 'de',
            'hrefLang' => 'de-DE',
            'direction' => '',
            'custom' => [
                'deeplTargetLanguage' => 'DE',
            ],
        ],
    ];

    private const PAGE_CONTEXT = 'The courts page of the tennis club, members book a tennis court online.';

    private const SITE_CONTEXT = 'The website of a tennis club in Berlin, for its members and guests.';

    /**
     * The texts and the context of each request to `/v2/translate`, the context `null` if it is not sent.
     *
     * @var list<array{texts: list<string>, context: string|null}>
     */
    private array $requests = [];

    protected function setUp(): void
    {
        $this->coreExtensionsToLoad[] = 'typo3/cms-workspaces';
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PagesWithTranslationContext.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)
            ->createFromUserPreferences($GLOBALS['BE_USER']);
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['deepltranslate-context'] = function (callable $handler): callable {
            return function (RequestInterface $request, array $options) use ($handler) {
                if (!str_ends_with($request->getUri()->getPath(), '/v2/translate')) {
                    return $handler($request, $options);
                }
                $body = json_decode((string)$request->getBody(), true);
                $texts = array_values(array_map(strval(...), (array)($body['text'] ?? [])));
                $this->requests[] = ['texts' => $texts, 'context' => isset($body['context']) ? (string)$body['context'] : null];
                $translations = array_map(
                    static fn(string $text): array => ['detected_source_language' => 'EN', 'text' => $text, 'billed_characters' => mb_strlen($text)],
                    $texts
                );
                return new FulfilledPromise(new Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    (string)json_encode(['translations' => $translations])
                ));
            };
        };
    }

    #[Test]
    public function contextOfThePageIsSentWithTheRecordsOnThePage(): void
    {
        $this->writeSite(self::SITE_CONTEXT);

        $this->translate('tt_content', 20);

        $this->assertNotSame([], $this->requests);
        $this->assertSame([self::PAGE_CONTEXT], array_values(array_unique(array_column($this->requests, 'context'))));
    }

    /**
     * The context itself is neither translated nor written into the translation of the page.
     */
    #[Test]
    public function contextOfThePageIsSentWithThePageAndNotTranslated(): void
    {
        $this->writeSite(self::SITE_CONTEXT);

        $this->translate('pages', 2);

        $this->assertNotSame([], $this->requests);
        $this->assertSame([self::PAGE_CONTEXT], array_values(array_unique(array_column($this->requests, 'context'))));
        $this->assertNotContains(self::PAGE_CONTEXT, array_merge(...array_column($this->requests, 'texts')));
    }

    /**
     * A context of whitespace only on the page counts as none.
     */
    #[Test]
    public function contextOfTheSiteIsSentWithoutContextOfThePage(): void
    {
        $this->writeSite(self::SITE_CONTEXT);

        $this->translate('tt_content', 30);

        $this->assertNotSame([], $this->requests);
        $this->assertSame([self::SITE_CONTEXT], array_values(array_unique(array_column($this->requests, 'context'))));
    }

    /**
     * The context of a page is not inherited by its subpages.
     */
    #[Test]
    public function contextOfTheSiteIsSentOnASubpageOfAPageWithContext(): void
    {
        $this->writeSite(self::SITE_CONTEXT);

        $this->translate('tt_content', 60);

        $this->assertNotSame([], $this->requests);
        $this->assertSame([self::SITE_CONTEXT], array_values(array_unique(array_column($this->requests, 'context'))));
    }

    /**
     * A record without a page, like the metadata of a file, gets no context, also after a record on a page with a
     * context was translated in the same request.
     */
    #[Test]
    public function noContextIsSentForARecordWithoutPageAfterARecordOnAPage(): void
    {
        $this->writeSite(self::SITE_CONTEXT);
        $this->translate('tt_content', 20);
        $this->requests = [];
        $this->get(ProcessingInstruction::class)->setProcessingInstruction('be_users', 1, true);
        $translateContext = new TranslateContext('Administrator');
        $translateContext->setSourceLanguageCode('EN');
        $translateContext->setTargetLanguageCode('DE');

        $this->get(DeeplService::class)->translateContent($translateContext);

        $this->assertSame([null], array_column($this->requests, 'context'));
    }

    #[Test]
    public function noContextIsSentWithoutContextOfPageAndSite(): void
    {
        $this->writeSite('');

        $this->translate('tt_content', 30);

        $this->assertNotSame([], $this->requests);
        $this->assertSame([null], array_values(array_unique(array_column($this->requests, 'context'))));
    }

    #[Test]
    public function contextOfThePageInTheCurrentWorkspaceIsSent(): void
    {
        $this->writeSite(self::SITE_CONTEXT);
        $GLOBALS['BE_USER']->workspace = 1;
        GeneralUtility::makeInstance(Context::class)->setAspect('workspace', new WorkspaceAspect(1));

        $this->translate('tt_content', 40);

        $this->assertNotSame([], $this->requests);
        $this->assertSame(
            ['The website of a law firm, we represent clients before the courts.'],
            array_values(array_unique(array_column($this->requests, 'context')))
        );
    }

    private function writeSite(string $context): void
    {
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                additionalRootConfiguration: $context !== '' ? ['deeplContext' => $context] : [],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'strict'),
            ],
        );
    }

    private function translate(string $table, int $uid): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [$table => [$uid => ['deepltranslate' => 1]]]);
        $dataHandler->process_cmdmap();
        $this->assertSame([], $dataHandler->errorLog);
    }
}
