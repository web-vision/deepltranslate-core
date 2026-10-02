<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Hooks;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

/**
 * A link DeepL loses is reported to the editor: the translation is kept and a warning names the field and the
 * link. DeepL is replaced by an HTTP handler that answers with the request text without its links.
 */
abstract class AbstractLostLinkTestCase extends AbstractDeepLTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF-8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => '', 'custom' => ['deeplTargetLanguage' => '']],
        'DE' => ['id' => 2, 'title' => 'Deutsch', 'locale' => 'de_DE', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => '', 'custom' => ['deeplTargetLanguage' => 'DE']],
    ];

    protected const BODYTEXT = '<p>Get the warranty extension for your bike on the <a href="t3://page?uid=5">service page</a>.</p>';

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(rootPageId: 1),
            languages: [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'strict'),
            ],
        );
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->createFromUserPreferences($GLOBALS['BE_USER']);
        $serverParams = array_replace($_SERVER, ['HTTP_HOST' => 'example.com', 'SCRIPT_NAME' => '/typo3/index.php']);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('http://example.com/typo3/index.php', 'GET', null, $serverParams))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', NormalizedParams::createFromServerParams($serverParams));
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['deepl-without-links'] = self::answerWithoutLinks();
        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content')->insert('tt_content', [
            'uid' => 10, 'pid' => 1, 'CType' => 'text', 'header' => 'Warranty', 'bodytext' => self::BODYTEXT,
            'sys_language_uid' => 0, 'colPos' => 0,
        ]);
    }

    protected function assertLostLinkMessage(): void
    {
        $messages = array_values(array_filter(
            $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages(),
            static fn(FlashMessage $message): bool => $message->getSeverity() === ContextualFeedbackSeverity::WARNING
        ));
        $this->assertCount(1, $messages);
        $this->assertSame('A link was lost in the translation', $messages[0]->getTitle());
        $this->assertSame(
            'The DeepL translation of field "bodytext" of record tt_content:10 lost the link to "t3://page?uid=5" on "service page". Please add the link to the translation again.',
            $messages[0]->getMessage()
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function fetchTranslation(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $row = $queryBuilder->select('*')->from('tt_content')->where(
            $queryBuilder->expr()->eq('l18n_parent', $queryBuilder->createNamedParameter(10, Connection::PARAM_INT)),
            $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(2, Connection::PARAM_INT)),
        )->executeQuery()->fetchAssociative();
        return is_array($row) ? $row : [];
    }

    private static function answerWithoutLinks(): callable
    {
        return static fn(callable $handler): callable => static function (RequestInterface $request, array $options) use ($handler) {
            if (!str_ends_with($request->getUri()->getPath(), '/v2/translate')) {
                return $handler($request, $options);
            }
            $texts = (array)(json_decode((string)$request->getBody(), true)['text'] ?? []);
            return Create::promiseFor(new Response(200, ['Content-Type' => 'application/json'], (string)json_encode([
                'translations' => array_map(
                    static fn(string $text): array => [
                        'detected_source_language' => 'EN',
                        'text' => (string)preg_replace('#<a\b[^>]*>(.*?)</a>#s', '$1', $text),
                        'billed_characters' => mb_strlen($text),
                    ],
                    $texts
                ),
            ])));
        };
    }
}
