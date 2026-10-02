<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Hooks;

use DeepL\TranslatorOptions;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\RequestInterface;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Hooks\TranslateHook;
use WebVision\Deepltranslate\Core\Service\ContentFormatResolver;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

/**
 * The DeepL mock server answers every line with a fixed word, so it cannot show what happens to markup. Here DeepL
 * returns the text it gets unchanged, so the stored translation must equal the source, and the sent text shows the
 * content format the hook used.
 */
#[CoversClass(TranslateHook::class)]
#[CoversClass(ContentFormatResolver::class)]
final class TranslateHookContentFormatTest extends AbstractDeepLTestCase
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

    /**
     * Texts sent to `/v2/translate`, in the order of the requests.
     *
     * @var list<string>
     */
    private array $sentTexts = [];

    /**
     * Replaces the identity answer of DeepL, if set.
     *
     * @var (\Closure(string): string)|null
     */
    private ?\Closure $answer = null;

    protected function setUp(): void
    {
        $this->coreExtensionsToLoad[] = 'typo3/cms-workspaces';
        $this->testExtensionsToLoad[] = __DIR__ . '/../Fixtures/Extensions/test_inline_relations';
        parent::setUp();
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
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)
            ->createFromUserPreferences($GLOBALS['BE_USER']);
    }

    /**
     * The client of the parent test case sends with the HTTP client of the DeepL library. The identity answer is an
     * HTTP handler of TYPO3, so the client is created with the HTTP client of TYPO3 here. Translations are answered
     * with the sent text, everything else, for example the supported languages, by the mock server.
     *
     * @param array<string, mixed> $options
     */
    protected function instantiateMockServerClient(array $options = []): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['deepltranslate-identity'] = function (callable $handler): callable {
            return function (RequestInterface $request, array $options) use ($handler) {
                if (!str_ends_with($request->getUri()->getPath(), '/v2/translate')) {
                    return $handler($request, $options);
                }
                $body = json_decode((string)$request->getBody(), true);
                $translations = [];
                foreach ((array)($body['text'] ?? []) as $text) {
                    $this->sentTexts[] = (string)$text;
                    $answer = $this->answer !== null ? ($this->answer)((string)$text) : (string)$text;
                    $translations[] = ['detected_source_language' => 'EN', 'text' => $answer, 'billed_characters' => mb_strlen((string)$text)];
                }
                return new FulfilledPromise(new Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    (string)json_encode(['translations' => $translations])
                ));
            };
        };
        parent::instantiateMockServerClient(
            [TranslatorOptions::HTTP_CLIENT => GeneralUtility::makeInstance(GuzzleClientFactory::class)->getClient()] + $options
        );
    }

    #[Test]
    public function fieldsOfContentElementAreTranslatedInTheirFormat(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $header = 'Kids < 12 & "Teens" use <b>not markup</b> &amp; more';
        $bodytext = '<p>Use &lt;b&gt; for bold &amp; <a href="t3://page?uid=1" title="Say &quot;hi&quot; &gt; now">more</a><br />Line&nbsp;two</p>';
        $this->insertContentElement(['uid' => 10, 'CType' => 'text', 'header' => $header, 'bodytext' => $bodytext]);

        $this->translate('tt_content', 10);

        $translation = $this->fetchTranslation('tt_content', 'l18n_parent', 10, ['header', 'bodytext']);
        static::assertSame($header, $translation['header']);
        static::assertSame($bodytext, $translation['bodytext']);
        // The order of the fields differs between the cores.
        static::assertEqualsCanonicalizing(
            [
                'Kids &lt; 12 &amp; "Teens" use &lt;b&gt;not markup&lt;/b&gt; &amp;amp; more',
                "<p>Use &lt;b&gt; for bold &amp; <a href=\"t3://page?uid=1\" title=\"Say &quot;hi&quot; &gt; now\" dlt-r=\"0\">more</a><br/>Line\u{A0}two</p>",
            ],
            $this->sentTexts
        );
    }

    /**
     * HTML in a code editor is translated like rich text, the code in `<script>` and `<style>` is kept, see the
     * `ignore_tags` of the client. The TYPO3 12 test instance has no `typo3/cms-t3editor`, there the field is a
     * textarea with the format `html` this extension declares.
     */
    #[Test]
    public function htmlOfPlainHtmlElementIsTranslatedWithItsCodeKept(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $bodytext = '<div class="teaser"><p>Opening hours</p></div><style>.teaser > p::after { content: "Open"; }</style>'
            . '<script>if (a < 768 && b > 0) { label = "Opening hours"; } // guard --></script>';
        $this->insertContentElement(['uid' => 10, 'CType' => 'html', 'header' => 'Opening hours', 'bodytext' => $bodytext]);

        $this->translate('tt_content', 10);

        static::assertSame($bodytext, $this->fetchTranslation('tt_content', 'l18n_parent', 10, ['bodytext'])['bodytext']);
        static::assertEqualsCanonicalizing(
            [
                'Opening hours',
                '<div class="teaser"><p>Opening hours</p></div><style>.teaser &gt; p::after { content: "Open"; }</style>'
                    . '<script>if (a &lt; 768 &amp;&amp; b &gt; 0) { label = "Opening hours"; } // guard --&gt;</script>',
            ],
            $this->sentTexts
        );
    }

    /**
     * Attribute names XML does not allow, used by JavaScript frameworks like Alpine.js, come back as in the source.
     * Only the element with such attributes carries a number to DeepL, its valid attributes are sent as they are.
     */
    #[Test]
    public function attributesOfPlainHtmlElementComeBackAsInTheSource(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $bodytext = '<div x-data="{ open: false }" @click.outside="open = false" :class="{ \'is-open\': open }">'
            . '<button type="button" @click="open = !open" x-bind:aria-expanded="open">Show the opening hours</button>'
            . '<p x-show="open" onclick="if (a < b && c) { track(); }">We are open from Monday to Friday.</p></div>';
        $this->insertContentElement(['uid' => 10, 'CType' => 'html', 'header' => 'Opening hours', 'bodytext' => $bodytext]);

        $this->translate('tt_content', 10);

        static::assertSame($bodytext, $this->fetchTranslation('tt_content', 'l18n_parent', 10, ['bodytext'])['bodytext']);
        static::assertContains(
            '<div x-data="{ open: false }" :class="{ \'is-open\': open }" dlt-a="0">'
                . '<button type="button" x-bind:aria-expanded="open" dlt-a="1">Show the opening hours</button>'
                . '<p x-show="open" onclick="if (a &lt; b &amp;&amp; c) { track(); }" dlt-a="2">We are open from Monday to Friday.</p></div>',
            $this->sentTexts
        );
    }

    /**
     * In a workspace, `DataHandler::localize()` passes the values of the workspace version to the hook, so the
     * format is resolved with that version too: the CType changed from "bullets" to "text" in the workspace makes
     * the bodytext rich text.
     */
    #[Test]
    public function fieldOfRecordChangedInWorkspaceIsResolvedWithItsWorkspaceVersion(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContentElementChangedInWorkspace.csv');
        $GLOBALS['BE_USER']->workspace = 1;
        GeneralUtility::makeInstance(Context::class)->setAspect('workspace', new WorkspaceAspect(1));

        $this->translate('tt_content', 10);

        static::assertSame(
            '<p>Use <strong>bold</strong> text</p>',
            $this->fetchTranslation('tt_content', 'l18n_parent', 10, ['bodytext'])['bodytext']
        );
        static::assertEqualsCanonicalizing(['List', '<p>Use <strong dlt-r="0">bold</strong> text</p>'], $this->sentTexts);
    }

    /**
     * Code other than HTML, here TypoScript, is not sent at all.
     */
    #[Test]
    public function codeOtherThanHtmlIsNotSentToDeepL(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        // Read by the resolver only, DataHandler calls the hook for every text field.
        $GLOBALS['TCA']['tt_content']['types']['html']['columnsOverrides']['bodytext']['config']['format'] = 'typoscript';
        $bodytext = "lib.label = TEXT\nlib.label.value = Opening hours\nlib.copy < lib.label";
        $this->insertContentElement(['uid' => 10, 'CType' => 'html', 'header' => 'Opening hours', 'bodytext' => $bodytext]);

        $this->translate('tt_content', 10);

        static::assertSame($bodytext, $this->fetchTranslation('tt_content', 'l18n_parent', 10, ['bodytext'])['bodytext']);
        static::assertSame(['Opening hours'], $this->sentTexts);
    }

    /**
     * The file reference is localized along with the content element. DataHandler calls the hook with the field
     * names of `sys_file_reference`, while the processing instruction still names the content element.
     */
    #[Test]
    public function fieldsOfFileReferenceLocalizedWithContentElementAreResolvedInTheirOwnTable(): void
    {
        // Records with fixed uids come from a data set, the import synchronizes the sequences of PostgreSQL.
        // Inserted directly, the localized file reference would get uid 1 there, which the default language
        // record already has.
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileReferenceOnContentElement.csv');
        @mkdir($this->instancePath . '/fileadmin', 0777, true);
        file_put_contents($this->instancePath . '/fileadmin/image.jpg', 'image');
        $reference = [
            'title' => 'Ref <title> & co',
            'description' => 'a < b & "c"',
            'alternative' => 'I </3 you & "me"',
        ];

        $this->translate('tt_content', 10);

        static::assertSame($reference, $this->fetchTranslation('sys_file_reference', 'l10n_parent', 1, array_keys($reference)));
        static::assertContains('Ref &lt;title&gt; &amp; co', $this->sentTexts);
    }

    /**
     * Parent and child have a field "title" with the same content, but rich text in the parent and plain text in
     * the child. Only the record `DataHandler::localize()` works on tells them apart.
     */
    #[Test]
    public function fieldOfInlineChildIsNotResolvedInTheParentWithSameFieldNameAndContent(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Regression/Fixtures/inlineRelationsParentLocalize.csv');
        // Read by the resolver only, DataHandler calls the hook for input and text fields alike.
        $GLOBALS['TCA']['tx_testinlinerelations_parent']['columns']['title']['config'] = ['type' => 'text', 'enableRichtext' => true];
        $title = 'a < b & "c"';
        $this->updateRecord('tx_testinlinerelations_parent', 1, ['title' => $title]);
        $this->updateRecord('tx_testinlinerelations_child_declared', 1, ['title' => $title]);

        $this->translate('tx_testinlinerelations_parent', 1);

        static::assertSame('a &lt; b &amp; "c"', $this->fetchTranslation('tx_testinlinerelations_parent', 'l10n_parent', 1, ['title'])['title']);
        static::assertSame($title, $this->fetchTranslation('tx_testinlinerelations_child_declared', 'l10n_parent', 1, ['title'])['title']);
    }

    /**
     * The child is localized along with its parent: `deepltranslate` for the parent record.
     */
    #[Test]
    public function fieldsOfInlineChildLocalizedWithItsParentKeepTheirFormat(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Regression/Fixtures/inlineRelationsParentLocalize.csv');
        $child = ['title' => 'a < b & "c"', 'description' => '<p>A &lt;b&gt; tag &amp; <strong>more</strong></p>'];
        $this->updateRecord('tx_testinlinerelations_child_declared', 1, $child);

        $this->translate('tx_testinlinerelations_parent', 1);

        static::assertSame($child, $this->fetchTranslation('tx_testinlinerelations_child_declared', 'l10n_parent', 1, array_keys($child)));
        static::assertContains('a &lt; b &amp; "c"', $this->sentTexts);
        // Rich text through the `overrideChildTca` of the parent field, sent as markup.
        static::assertContains('<p>A &lt;b&gt; tag &amp; <strong dlt-r="0">more</strong></p>', $this->sentTexts);
    }

    /**
     * The child is translated on its own: `deepltranslate` for the child record, handed over to the
     * `inlineLocalizeSynchronize` command of the translated parent.
     */
    #[Test]
    public function fieldsOfInlineChildTranslatedOnItsOwnKeepTheirFormat(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Regression/Fixtures/inlineRelationsChildLocalize.csv');
        $child = ['title' => 'a < b & "c"', 'description' => '<p>A &lt;b&gt; tag &amp; <strong>more</strong></p>'];
        $this->updateRecord('tx_testinlinerelations_child_declared', 3, $child);

        $this->translate('tx_testinlinerelations_child_declared', 3);

        static::assertSame($child, $this->fetchTranslation('tx_testinlinerelations_child_declared', 'l10n_parent', 3, array_keys($child)));
        static::assertEqualsCanonicalizing(['a &lt; b &amp; "c"', '<p>A &lt;b&gt; tag &amp; <strong dlt-r="0">more</strong></p>'], $this->sentTexts);
    }

    /**
     * The warning names the field and the record, and it is kept in the session, so the editor sees it after the
     * AJAX request of the localization wizard and after the redirect of `tce_db` as well.
     */
    #[Test]
    public function failedTranslationOfAFieldIsReportedWithItsRecord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $bodytext = '<p>Text</p>';
        $this->insertContentElement(['uid' => 10, 'CType' => 'text', 'header' => 'Header', 'bodytext' => $bodytext]);
        // Not well-formed XML, the conversion back fails.
        $this->answer = static fn (string $text): string => str_starts_with($text, '<p>') ? '<p>Text' : $text;

        $this->translate('tt_content', 10);

        static::assertSame($bodytext, $this->fetchTranslation('tt_content', 'l18n_parent', 10, ['bodytext'])['bodytext']);
        $messages = $this->warnings();
        static::assertCount(1, $messages);
        static::assertSame('The translation of a field failed', $messages[0]->getTitle());
        static::assertSame(
            'The DeepL translation of field "bodytext" of record tt_content:10 failed. The field keeps the text of the source language.',
            $messages[0]->getMessage()
        );
        static::assertTrue($messages[0]->isSessionMessage());
    }

    /**
     * The link is lost in the field of an inline child localized along with its parent, the warning names the
     * child record, not the parent the translation was started for.
     */
    #[Test]
    public function lostLinkInFieldOfInlineChildIsReportedWithTheChildRecord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Regression/Fixtures/inlineRelationsParentLocalize.csv');
        $this->updateRecord('tx_testinlinerelations_child_declared', 1, [
            'description' => '<p>See the <a href="t3://page?uid=5">service page</a>.</p>',
        ]);
        $this->answer = static fn (string $text): string => (string)preg_replace('#<a\b[^>]*>(.*?)</a>#s', '$1', $text);

        $this->translate('tx_testinlinerelations_parent', 1);

        $messages = $this->warnings();
        static::assertCount(1, $messages);
        static::assertSame(
            'The DeepL translation of field "description" of record tx_testinlinerelations_child_declared:1 lost the link to "t3://page?uid=5" on "service page". Please add the link to the translation again.',
            $messages[0]->getMessage()
        );
    }

    /**
     * @return list<FlashMessage>
     */
    private function warnings(): array
    {
        return array_values(array_filter(
            $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages(),
            static fn (FlashMessage $message): bool => $message->getSeverity() === ContextualFeedbackSeverity::WARNING
        ));
    }

    /**
     * @param array<string, mixed> $values
     */
    private function insertContentElement(array $values): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content')
            ->insert('tt_content', $values + ['pid' => 1, 'sys_language_uid' => 0, 'colPos' => 0]);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function updateRecord(string $table, int $uid, array $values): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table)
            ->update($table, $values, ['uid' => $uid]);
    }

    private function translate(string $table, int $uid): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [$table => [$uid => ['deepltranslate' => 1]]]);
        $dataHandler->process_cmdmap();
        static::assertSame([], $dataHandler->errorLog);
    }

    /**
     * @param list<string> $fields
     * @return array<string, mixed>
     */
    private function fetchTranslation(string $table, string $pointerField, int $uid, array $fields): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $row = $queryBuilder
            ->select(...$fields)
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq($pointerField, $queryBuilder->createNamedParameter($uid)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(1)),
            )
            ->executeQuery()
            ->fetchAssociative();
        static::assertIsArray($row, sprintf('No translation of %s:%d', $table, $uid));
        return $row;
    }
}
