<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\RealApi;

use GuzzleHttp\Middleware;
use Masterminds\HTML5;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Information\Typo3Version;
use WebVision\Deepltranslate\Core\Domain\Dto\TranslateContext;
use WebVision\Deepltranslate\Core\Domain\Enum\ContentFormat;
use WebVision\Deepltranslate\Core\Service\DeeplService;
use WebVision\Deepltranslate\Core\Service\ProcessingInstruction;

/**
 * Sends the content of reported issues through the real translation path to the real DeepL API and checks both
 * that it was translated and that its structure survived. DeepL changes its models without notice, this group
 * is the tripwire for that.
 *
 * Every run is billed per character, so the group is excluded from `runTests.sh -s functional` and CI. Run it
 * locally with your own API key, read with `read -rs` to keep it out of the shell history:
 *
 * ```
 * read -rs DEEPL_AUTH_KEY && export DEEPL_AUTH_KEY
 * Build/Scripts/runTests.sh -s functionalDeepLApi
 * ```
 *
 * To review a failure, set `DEEPL_REAL_API_LOG` to a file below the extension directory, for example with
 * `CI_PARAMS="-e DEEPL_REAL_API_LOG=$PWD/.Build/deepl-real-api.jsonl"`: every request text, the raw answer of
 * DeepL and the billed characters are appended to it as JSON lines. The key is never written.
 */
#[Group('deepl-real-api')]
final class RichTextTagHandlingTest extends FunctionalTestCase
{
    /**
     * Elements the structure check compares in document order.
     */
    private const BLOCK_ELEMENTS = [
        'address', 'blockquote', 'br', 'caption', 'dd', 'div', 'dl', 'dt', 'figcaption', 'figure', 'h1', 'h2', 'h3',
        'h4', 'h5', 'h6', 'hr', 'li', 'ol', 'p', 'pre', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'ul',
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
            static::markTestSkipped('Requires a real DeepL API key in DEEPL_AUTH_KEY and no DeepL mock server.');
        }
        if ((new Typo3Version())->getMajorVersion() >= 13) {
            $this->coreExtensionsToLoad[] = 'typo3/cms-install';
        }
        parent::setUp();
        // Set at runtime only, so the key is never written into the settings file of the test instance.
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['deepltranslate_core']['apiKey'] = $authKey;
        // Keeps the raw answer of DeepL for the failure messages and the log.
        $this->exchanges = new \ArrayObject();
        $exchanges = $this->exchanges;
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['deepl-real-api-history'] = Middleware::history($exchanges);
        $this->get(ProcessingInstruction::class)->setProcessingInstruction(null, null, true);
    }

    /**
     * The content of the issues, verbatim where the issue has markup, otherwise the content of the dev instance
     * element named. PHPUnit 10 passes the values of a data set by position, not by name, so a data set lists
     * every argument up to the last one it sets.
     */
    public static function contentDataProvider(): \Generator
    {
        $issue489 = '<p>' . "\n"
            . '    <i>Mehr über mögliche Einsatzbereiche zeigt Ihnen gerne unsere </i><a href="t3://page?uid=21#5017"><i>Studienberatung</i></a><i> auf.</i>' . "\n"
            . '</p>';
        yield 'issue 489, italic text around a link, to English' => [
            'content' => $issue489,
            'source' => 'DE',
            'target' => 'EN-GB',
        ];
        yield 'issue 489, italic text around a link, to French' => [
            'content' => $issue489,
            'source' => 'DE',
            'target' => 'FR',
        ];
        yield 'issue 665, buttons as reported, element 10002' => [
            'content' => '<p><a class="button button--whatsapp" href="https://api.whatsapp.com/send?phone=123456789" title="Rent a sports car and ask on Whatsapp">Whatsapp </a>'
                . '<a class="button button--call" href="tel:+491234567" title="Rent a sports car and call us">Call</a>'
                . '<a class="button button--book" href="t3://page?uid=40" title="Rent a sports car and book it">Book</a></p>',
            'source' => 'EN',
            'target' => 'DE',
            'expectedFragments' => [],
            'contentFormat' => ContentFormat::RichText,
            'checks' => ['linkTexts'],
        ];
        yield 'issue 665, buttons without any space, element 10003' => [
            'content' => '<p><a class="button" href="https://api.whatsapp.com/send?phone=123456789">Whatsapp</a><a class="button" href="tel:+491234567">Call</a><a class="button" href="t3://page?uid=40">Book</a></p>',
            'source' => 'EN',
            'target' => 'DE',
            'expectedFragments' => [],
            'contentFormat' => ContentFormat::RichText,
            'checks' => ['linkTexts'],
        ];
        yield 'adjacent links in running text and in a list, element 10004' => [
            'content' => '<p>Reach us by <a href="https://api.whatsapp.com/send?phone=123456789">Whatsapp</a><a href="tel:+491234567">phone</a><a href="mailto:hello@example.org">mail</a> or on the <a href="t3://page?uid=40">contact page</a>.</p>' . "\r\n"
                . '<ul> <li><a href="t3://page?uid=20">Features</a><a href="t3://page?uid=30">About</a></li> <li><strong><a href="t3://page?uid=40">Contact</a></strong><em><a href="t3://page?uid=10">Home</a></em></li> </ul>',
            'source' => 'EN',
            'target' => 'DE',
            'expectedFragments' => [],
            'contentFormat' => ContentFormat::RichText,
            'checks' => ['linkTexts'],
        ];
        yield 'issue 642, line breaks with newlines' => [
            'content' => '<p>' . "\n"
                . '    Postal address:<br>' . "\n"
                . '    P.O. Box 1234<br>' . "\n"
                . '    12345 Sample City<br>' . "\n"
                . '    <br>' . "\n"
                . '    Office address:<br>' . "\n"
                . '    Sample Building<br>' . "\n"
                . '    Sample Street 1<br>' . "\n"
                . '    12345 Sample City<br>' . "\n"
                . '</p>',
            'source' => 'EN',
            'target' => 'ES',
        ];
        yield 'issue 642, line breaks without newlines, element 11002' => [
            'content' => '<p>Postal address:<br>P.O. Box 1234<br>12345 Sample City<br><br>Office address:<br>Sample Building<br>Sample Street 1<br>12345 Sample City</p>',
            'source' => 'EN',
            'target' => 'DE',
        ];
        yield 'issue 65, line break in the form TYPO3 stores' => [
            'content' => '<p>Our office is closed on Mondays.<br />Please call us from Tuesday to Friday.</p>',
            'source' => 'EN',
            'target' => 'DE',
        ];
        yield 'issue 278, spaces around inline elements, element 13002' => [
            'content' => '<p>Important species in blueberry include the Western flower thrips (<em>Frankliniella occidentalis</em>) and Chilli thrips (<em>Scirtothrips dorsalis</em>).</p>' . "\r\n"
                . '<p>The parasitic wasps<em>&nbsp;Diglyphus</em>&nbsp;<em>isaea</em>&nbsp;(<a href="t3://page?uid=30">Miglyphus</a>) and<em>&nbsp;Dacnusa sibirica</em>&nbsp;(<a href="t3://page?uid=30">Minusa</a>), are effective natural enemies of leaf miner larvae.</p>',
            'source' => 'EN',
            'target' => 'FR',
            'expectedFragments' => [],
            'contentFormat' => ContentFormat::RichText,
            'checks' => ['spaces'],
        ];
        yield 'issue 311 and DPL-13, words glued to elements, element 13004' => [
            'content' => '<p>Try the new FOOBAR<sup>®</sup> now!</p>' . "\r\n"
                . '<p>Our<strong>new</strong>offer starts today: the<em>extended</em>warranty, the free<a href="t3://page?uid=40">delivery</a>and the<u>personal</u>advice.</p>' . "\r\n"
                . '<p>Prefixes like e<strong>Mail</strong> and i<em>Phone</em> and units like 10<sup>3</sup>&nbsp;kg stay as they are.</p>',
            'source' => 'EN',
            'target' => 'DE',
            'expectedFragments' => ['FOOBAR<sup>®</sup>', '10<sup>3</sup>'],
        ];
        yield 'issue 311, single letter tokens in a German sentence' => [
            'content' => '<p>Das neue i<em>Phone</em> ist da, schreiben Sie uns eine e<strong>Mail</strong>.</p>',
            'source' => 'DE',
            'target' => 'EN-GB',
            'expectedFragments' => [],
            'contentFormat' => ContentFormat::RichText,
            'checks' => [],
            'knownLimitation' => 'A single styled letter has no word of its own, DeepL can move it into its element or drop the element (English dropped the <em> of "iPhone" in an earlier call).',
        ];
        $compounds = '<p>Unsere Produkt<strong>neuheiten</strong> sind da, und die <strong>Versand</strong>kosten sinken.</p>';
        yield 'partially formatted compounds of a German source, to English' => [
            'content' => $compounds,
            'source' => 'DE',
            'target' => 'EN-GB',
        ];
        yield 'partially formatted compounds of a German source, to French' => [
            'content' => $compounds,
            'source' => 'DE',
            'target' => 'FR',
        ];
        $linkedCompound = '<p>Das ist die Garantie<em>verlängerung</em> für Ihr Fahr<a href="t3://page?uid=5">rad</a>.</p>';
        $lostLink = 'A link on part of a compound is lost when the translation is one word, here "bicycle".'
            . ' The pull request before the preparation lost it in English and French as well. The converter logs a warning.';
        yield 'a link on part of a compound, to English' => [
            'content' => $linkedCompound,
            'source' => 'DE',
            'target' => 'EN-GB',
            'expectedFragments' => [],
            'contentFormat' => ContentFormat::RichText,
            'checks' => [],
            'knownLimitation' => $lostLink,
        ];
        yield 'a link on part of a compound, to French' => [
            'content' => $linkedCompound,
            'source' => 'DE',
            'target' => 'FR',
            'expectedFragments' => [],
            'contentFormat' => ContentFormat::RichText,
            'checks' => [],
            'knownLimitation' => $lostLink,
        ];
        yield 'DPL-89, digits and letters before sup and sub, element 13006' => [
            'content' => '<p>Hello1<sup>2</sup>, this sentence has a footnote marker.</p>' . "\r\n"
                . '<p>The room has 25 m<sup>2</sup>, the tank holds 3 m<sup>3</sup> of water (H<sub>2</sub>O), and the formula is E = mc<sup>2</sup>.</p>' . "\r\n"
                . '<p>Footnote markers after numbers: 2021<sup>1</sup>, 2022<sup>2</sup> and 2023<sup>3</sup>.</p>',
            'source' => 'EN',
            'target' => 'DE',
            'expectedFragments' => ['1<sup>2</sup>', 'm<sup>2</sup>', 'm<sup>3</sup>', 'H<sub>2</sub>O', 'mc<sup>2</sup>', '2021<sup>1</sup>', '2022<sup>2</sup>', '2023<sup>3</sup>'],
        ];
        yield 'nested inline markup, element 13007' => [
            'content' => '<p>Text with <strong>bold</strong>, <em>italic</em>, <strong><em>bold and italic</em></strong>, <u>underlined</u>, <s>struck</s>, <code>code</code>, and a <a href="t3://page?uid=20"><strong>bold link</strong> with <em>italic</em> text</a>.</p>' . "\r\n"
                . '<p><strong>Important: <em>read the <a href="t3://page?uid=40">terms</a> first</em>, then sign.</strong></p>',
            'source' => 'EN',
            'target' => 'DE',
        ];
        yield 'issue 507, non-breaking spaces, element 12002' => [
            'content' => '<h4>&nbsp;</h4>' . "\r\n"
                . '<p>The price is 100&nbsp;€ per month, the setup costs 50&nbsp;€ once.</p>' . "\r\n"
                . '<p>Call us on +49&nbsp;1234&nbsp;567 between 9&nbsp;a.m. and 5&nbsp;p.m.</p>',
            'source' => 'EN',
            'target' => 'DE',
        ];
        yield 'issue 508, unclosed line break and inline elements, element 12003' => [
            'content' => '<p>The first part of the text<br>and the second part of the text, with <strong>bold</strong> words and a <a href="t3://page?uid=40">link</a>.</p>',
            'source' => 'EN',
            'target' => 'DE',
        ];
        yield 'DPL-84, literal less-than and greater-than signs, element 12007' => [
            'content' => '<p>Children &lt; 12 years travel for free, adults &gt; 65 years get a discount of 20 %.</p>' . "\r\n"
                . '<p>The rule is simple: if the weight is &gt; 30 kg, the parcel goes by freight, otherwise by post.</p>',
            'source' => 'EN',
            'target' => 'DE',
        ];
        yield 'DPL-84, comparisons in a list as reported' => [
            'content' => '<ul>'
                . '<li>Gegendruck: irreguläre Partikel &gt; sphärische Partikel &gt; Core-Shell Partikel</li>'
                . '<li>Analysenzeit: Core-Shell Partikel &lt; irreguläre Partikel ≈ sphärische Partikel</li>'
                . '<li>log P &lt; -3: <a href="https://www.mz-at.de/de/chromatographie/trenntechniken/ionenaustauschchromatographie.html"><strong>Ionenaustausch</strong></a></li>'
                . '</ul>',
            'source' => 'DE',
            'target' => 'EN-GB',
        ];
        yield 'escaped markup, quotes and ampersand in attributes, element 12005' => [
            'content' => '<p>Use &lt;b&gt; for bold text &amp; &lt;i&gt; for italic text in the old markup.</p>' . "\r\n"
                . '<p><a href="https://example.org/office?floor=1&amp;room=2" title="Learn &quot;everything&quot; about the office">Visit the office</a> and&nbsp;say hello.</p>',
            'source' => 'EN',
            'target' => 'FR',
            'expectedFragments' => ['&lt;b&gt;', '&lt;i&gt;', 'href="https://example.org/office?floor=1&amp;room=2"', 'title="Learn &quot;everything&quot; about the office"'],
        ];
        yield 'list, element 15001' => [
            'content' => '<ul>' . "\r\n"
                . '<li>First item with <strong>bold text</strong>' . "\r\n"
                . '<ul>' . "\r\n" . '<li>Nested item one</li>' . "\r\n" . '<li>Nested item two with <a href="t3://page?uid=30">a link</a></li>' . "\r\n" . '</ul>' . "\r\n"
                . '</li>' . "\r\n" . '<li>Second item</li>' . "\r\n" . '</ul>' . "\r\n"
                . '<ol>' . "\r\n" . '<li>Open the page module.</li>' . "\r\n" . '<li>Choose the language.</li>' . "\r\n" . '<li>Translate with DeepL.</li>' . "\r\n" . '</ol>',
            'source' => 'EN',
            'target' => 'DE',
        ];
        yield 'table, element 15003' => [
            'content' => '<table class="table">' . "\r\n"
                . '<caption>Delivery times by region</caption>' . "\r\n"
                . '<thead>' . "\r\n" . '<tr><th>Region</th><th>Delivery time</th><th>Note</th></tr>' . "\r\n" . '</thead>' . "\r\n"
                . '<tbody>' . "\r\n"
                . '<tr><td>Germany</td><td>One to two days</td><td>Free from 50 euros</td></tr>' . "\r\n"
                . '<tr><td>Europe</td><td>Three to five days</td><td>Customs may apply</td></tr>' . "\r\n"
                . '<tr><td>Rest of the world</td><td>Up to three weeks</td><td>Ask us first</td></tr>' . "\r\n"
                . '</tbody>' . "\r\n" . '</table>',
            'source' => 'EN',
            'target' => 'DE',
        ];
        yield 'characters XML does not allow, a comment with double hyphens and a processing instruction' => [
            'content' => "<p>Die erste Zeile\u{B}und die zweite Zeile\u{C}gehören zu einem Absatz mit Steuerzeichen\u{1}.<!-- Hinweis -- intern --><?xml version=\"1.0\"?></p>",
            'source' => 'DE',
            'target' => 'EN-GB',
        ];
        yield 'translate="no" and class="notranslate"' => [
            'content' => '<p>Wir lieben <span class="notranslate">Schwarzwälder Kirschtorte</span> und <span translate="no">Apfelstrudel</span>.</p>',
            'source' => 'DE',
            'target' => 'EN-GB',
            'expectedFragments' => ['<span class="notranslate">Schwarzwälder Kirschtorte</span>', '<span translate="no">Apfelstrudel</span>'],
        ];
        yield 'issues 519 and 86, ampersand in a plain text heading' => [
            'content' => 'Unsere Leistungen & Preise für Familien',
            'source' => 'DE',
            'target' => 'EN-GB',
            'expectedFragments' => [],
            'contentFormat' => ContentFormat::PlainText,
        ];
        $style = '<style>' . "\n" . '.opening-hours h3::after { content: "Open today"; }' . "\n" . '.opening-hours > p { margin: 0 0 1em; }' . "\n" . '</style>';
        $script = '<script>' . "\n" . 'var label = "Opening hours";' . "\n"
            . 'if (window.innerWidth < 768 && label.length > 0) { console.log("Closed today"); }' . "\n"
            . '// legacy guard -->' . "\n" . '</script>';
        $comment = '<!-- Opening hours widget, keep in sync with the shop -->';
        yield 'content element "Plain HTML" with a comment, style and script' => [
            'content' => '<div class="opening-hours" data-label="Opening hours">' . "\n" . $comment . "\n"
                . '<h3>Opening hours</h3>' . "\n"
                . '<p>We are open from Monday to Friday. <a href="#map" title="Show the map">Find us</a></p>' . "\n"
                . '<button type="button" class="js-toggle" aria-label="Show all opening hours">Show more</button>' . "\n"
                . '</div>' . "\n" . $style . "\n" . $script,
            'source' => 'EN',
            'target' => 'DE',
            'expectedFragments' => [$comment, $style, $script],
        ];
        $alpineOpen = '<div x-data="{ open: false }" @click.outside="open = false" :class="{ \'is-open\': open }">';
        $alpineButton = '<button type="button" title="Show the opening hours" @click="open = !open" x-bind:aria-expanded="open">';
        $alpine = $alpineOpen . "\n" . $alpineButton . 'Opening hours</button>' . "\n"
            . '<p x-show="open" onclick="if (a < b && c) { track(); }">We are open from Monday to Friday. <a href="#map" title="Show the map">Find us</a></p>' . "\n"
            . '</div>';
        foreach (['DE', 'FR'] as $target) {
            yield 'content element "Plain HTML" with Alpine.js attributes, to ' . $target => [
                'content' => $alpine,
                'source' => 'EN',
                'target' => $target,
                'expectedFragments' => [
                    $alpineOpen,
                    $alpineButton,
                    '<p x-show="open" onclick="if (a < b && c) { track(); }">',
                    'title="Show the map"',
                ],
            ];
        }
    }

    /**
     * @param list<string> $expectedFragments markup that must be part of the translation as it is
     * @param list<string> $checks further checks: `spaces` (issue #278), `linkTexts` (issue #665)
     * @param string $knownLimitation reason to mark the test incomplete instead of failing when a link or an element is lost
     */
    #[Test]
    #[DataProvider('contentDataProvider')]
    public function contentIsTranslatedAndKeepsItsStructure(
        string $content,
        string $source,
        string $target,
        array $expectedFragments = [],
        ContentFormat $contentFormat = ContentFormat::RichText,
        array $checks = [],
        string $knownLimitation = '',
    ): void {
        $translateContext = new TranslateContext($content);
        $translateContext->setSourceLanguageCode($source);
        $translateContext->setTargetLanguageCode($target);
        $translateContext->setContentFormat($contentFormat);

        $translated = $this->get(DeeplService::class)->translateContent($translateContext);

        $this->writeLog($translated);
        $message = sprintf("Translation:\n%s\nAnswer of DeepL:\n%s", $translated, $this->lastAnswer());
        if ($knownLimitation !== ''
            && ($this->linkTargets($translated) !== $this->linkTargets($content)
                || count($this->elements($translated)) !== count($this->elements($content)))
        ) {
            static::markTestIncomplete('Known limitation: ' . $knownLimitation . "\n" . $message);
        }
        if ($contentFormat === ContentFormat::PlainText) {
            $this->assertTranslated($content, $translated, $message);
            static::assertSame(substr_count($content, '&'), substr_count($translated, '&'), $message);
            static::assertStringNotContainsString('&amp;', $translated, $message);
        } else {
            $this->assertTranslated($this->visibleText($content), $this->visibleText($translated), $message);
            $this->assertBlocksTranslated($content, $translated, $message);
            $this->assertSameStructure($content, $translated, $message);
            $this->assertLinesStartWithoutPunctuation($translated, $message);
            $this->assertNoWordGluedToElements($content, $translated, $message);
            if (in_array('spaces', $checks, true)) {
                $this->assertSameSpacesAroundInlineElements($content, $translated, $message);
            }
            if (in_array('linkTexts', $checks, true)) {
                $this->assertEachLinkHasItsOwnText($content, $translated, $message);
            }
        }
        $this->assertNoHelperLeft($content, $translated, $message);
        foreach ($expectedFragments as $fragment) {
            static::assertStringContainsString($fragment, $translated, $message);
        }
    }

    /**
     * The text changed and is not much shorter than the source: a lost clause, like in issue #489, makes the
     * translation much shorter.
     */
    private function assertTranslated(string $sourceText, string $translatedText, string $message): void
    {
        static::assertNotSame('', $translatedText, 'Nothing was translated. ' . $message);
        static::assertNotSame($sourceText, $translatedText, 'The text was not translated. ' . $message);
        static::assertGreaterThanOrEqual(
            0.6 * mb_strlen($sourceText),
            mb_strlen($translatedText),
            'The translation is much shorter than the source. ' . $message
        );
    }

    /**
     * Every text block with more than a few words is translated as a whole.
     */
    private function assertBlocksTranslated(string $content, string $translated, string $message): void
    {
        $translatedBlocks = $this->textBlocks($translated);
        foreach ($this->textBlocks($content) as $index => $sourceText) {
            if (mb_strlen($sourceText) < 20) {
                continue;
            }
            static::assertGreaterThanOrEqual(
                0.5 * mb_strlen($sourceText),
                mb_strlen($translatedBlocks[$index] ?? ''),
                sprintf('Block %d "%s" is much shorter in the translation. %s', $index, $sourceText, $message)
            );
        }
    }

    /**
     * Every element exists as often as in the source, with the same attributes, in the same ancestors, holding
     * words or not like in the source: in issue #489, `<i> auf.</i>` came back as `<i> .</i>`. The block elements,
     * `<br>` included, are in the same order. `<sup>` and `<sub>` hold the same text.
     */
    private function assertSameStructure(string $content, string $translated, string $message): void
    {
        $sourceElements = $this->elements($content);
        $translatedElements = $this->elements($translated);
        static::assertSame(
            array_column(array_filter($sourceElements, static fn (array $element): bool => $element['block']), 'signature'),
            array_column(array_filter($translatedElements, static fn (array $element): bool => $element['block']), 'signature'),
            'The block elements differ. ' . $message
        );
        $sourceSignatures = array_column($sourceElements, 'signature');
        $translatedSignatures = array_column($translatedElements, 'signature');
        sort($sourceSignatures);
        sort($translatedSignatures);
        static::assertSame($sourceSignatures, $translatedSignatures, 'The elements differ. ' . $message);
        $sourceScripts = array_column(array_filter($sourceElements, static fn (array $element): bool => $element['script']), 'text');
        $translatedScripts = array_column(array_filter($translatedElements, static fn (array $element): bool => $element['script']), 'text');
        sort($sourceScripts);
        sort($translatedScripts);
        static::assertSame($sourceScripts, $translatedScripts, 'The text of <sup> and <sub> differs. ' . $message);
    }

    /**
     * Issue #642: DeepL joined lines into sentences and started lines with `,` or `.`.
     */
    private function assertLinesStartWithoutPunctuation(string $translated, string $message): void
    {
        foreach ($this->parse($translated)->getElementsByTagName('br') as $lineBreak) {
            $line = ltrim((string)$lineBreak->nextSibling?->textContent);
            static::assertStringStartsNotWith(',', $line, $message);
            static::assertStringStartsNotWith('.', $line, $message);
        }
    }

    /**
     * No element is glued to a word of more than one character more often than in the source: issue #311 pulled
     * words into elements and split a link into `Versan<a>d</a> un<a>d</a>`. Counted per element name, without
     * knowing the wording.
     */
    private function assertNoWordGluedToElements(string $content, string $translated, string $message): void
    {
        $sourceGlue = $this->countGluedSides($content);
        foreach ($this->countGluedSides($translated) as $name => $count) {
            static::assertLessThanOrEqual($sourceGlue[$name] ?? 0, $count, sprintf('A word is glued to <%s>. %s', $name, $message));
        }
    }

    /**
     * Issue #278: the whitespace before and after each inline element, outside or as its first or last
     * character, is where it was.
     */
    private function assertSameSpacesAroundInlineElements(string $content, string $translated, string $message): void
    {
        static::assertSame($this->spacesAroundInlineElements($content), $this->spacesAroundInlineElements($translated), $message);
    }

    /**
     * Issue #665: every link has words of its own, no two links have the same text, and no link has the source
     * text of another link.
     */
    private function assertEachLinkHasItsOwnText(string $content, string $translated, string $message): void
    {
        $sourceTexts = $this->linkTexts($content);
        $texts = $this->linkTexts($translated);
        static::assertCount(count($sourceTexts), $texts, $message);
        static::assertSame(count($texts), count(array_unique($texts)), 'Two links have the same text. ' . $message);
        foreach ($texts as $index => $text) {
            static::assertMatchesRegularExpression('/[\p{L}\p{N}]/u', $text, $message);
            foreach ($sourceTexts as $sourceIndex => $sourceText) {
                if ($sourceIndex !== $index && $sourceText !== $sourceTexts[$index]) {
                    static::assertNotSame($sourceText, $text, sprintf('Link %d has the text of link %d. %s', $index, $sourceIndex, $message));
                }
            }
        }
    }

    private function assertNoHelperLeft(string $content, string $translated, string $message): void
    {
        static::assertDoesNotMatchRegularExpression('/[< ]dlt-/', $translated, $message);
        static::assertDoesNotMatchRegularExpression('/[\x{200B}\x{2060}]/u', $translated, $message);
        if (preg_match('/[⁰¹²³⁴⁵⁶⁷⁸⁹₀₁₂₃₄₅₆₇₈₉]/u', $content) !== 1) {
            static::assertDoesNotMatchRegularExpression('/[⁰¹²³⁴⁵⁶⁷⁸⁹₀₁₂₃₄₅₆₇₈₉]/u', $translated, $message);
        }
    }

    /**
     * @return list<array{signature: string, block: bool, script: bool, text: string}>
     */
    private function elements(string $html): array
    {
        $elements = [];
        $collect = static function (\DOMNode $parent, string $path) use (&$collect, &$elements): void {
            foreach ($parent->childNodes as $node) {
                if (!$node instanceof \DOMElement) {
                    continue;
                }
                $attributes = [];
                foreach ($node->attributes as $attribute) {
                    $attributes[] = $attribute->name . '="' . $attribute->value . '"';
                }
                sort($attributes);
                $hasWords = preg_match('/[\p{L}\p{N}]/u', $node->textContent) === 1;
                $elements[] = [
                    'signature' => sprintf('%s/%s[%s]%s', $path, $node->localName, implode(' ', $attributes), $hasWords ? '' : ' without words'),
                    'block' => in_array($node->localName, self::BLOCK_ELEMENTS, true),
                    'script' => in_array($node->localName, ['sup', 'sub'], true),
                    'text' => $node->textContent,
                ];
                $collect($node, $path . '/' . $node->localName);
            }
        };
        $collect($this->parse($html), '');
        return $elements;
    }

    /**
     * @return array<string, int> per element name, how many sides of its elements touch a word of more than one
     *                            character outside
     */
    private function countGluedSides(string $html): array
    {
        $counts = [];
        foreach ((new \DOMXPath($this->parse($html)))->query('//*') ?: [] as $element) {
            if (!$element instanceof \DOMElement || $element->localName === 'root') {
                continue;
            }
            $before = $element->previousSibling;
            $after = $element->nextSibling;
            $glued = (int)($before instanceof \DOMText
                    && preg_match('/[\p{L}\p{N}]{2,}\z/u', $before->data) === 1
                    && preg_match('/\A[\p{L}\p{N}]/u', $element->textContent) === 1)
                + (int)($after instanceof \DOMText
                    && preg_match('/\A[\p{L}\p{N}]{2,}/u', $after->data) === 1
                    && preg_match('/[\p{L}\p{N}]\z/u', $element->textContent) === 1);
            $counts[(string)$element->localName] = ($counts[(string)$element->localName] ?? 0) + $glued;
        }
        return $counts;
    }

    /**
     * @return list<string> per inline element in document order, whether whitespace is before and after it
     */
    private function spacesAroundInlineElements(string $html): array
    {
        $spaces = [];
        foreach ((new \DOMXPath($this->parse($html)))->query('//em|//i|//strong|//b|//a|//span|//u|//code') ?: [] as $element) {
            if (!$element instanceof \DOMElement) {
                continue;
            }
            $before = $element->previousSibling instanceof \DOMText ? $element->previousSibling->data : '';
            $after = $element->nextSibling instanceof \DOMText ? $element->nextSibling->data : '';
            $spaces[] = sprintf(
                '%s: %s|%s',
                $element->localName,
                preg_match('/[\s\x{A0}]\z/u', $before) === 1 || preg_match('/\A[\s\x{A0}]/u', $element->textContent) === 1 ? 'space' : 'none',
                preg_match('/\A[\s\x{A0}]/u', $after) === 1 || preg_match('/[\s\x{A0}]\z/u', $element->textContent) === 1 ? 'space' : 'none'
            );
        }
        return $spaces;
    }

    /**
     * @return list<string> the target of every link in document order
     */
    private function linkTargets(string $html): array
    {
        $targets = [];
        foreach ($this->parse($html)->getElementsByTagName('a') as $link) {
            $targets[] = $link->getAttribute('href');
        }
        return $targets;
    }

    /**
     * @return list<string> the normalized text of every link in document order
     */
    private function linkTexts(string $html): array
    {
        $texts = [];
        foreach ($this->parse($html)->getElementsByTagName('a') as $link) {
            $texts[] = mb_strtolower($this->normalizeSpace($link->textContent), 'UTF-8');
        }
        return $texts;
    }

    /**
     * @return list<string> the text of the elements holding sentences, in document order
     */
    private function textBlocks(string $html): array
    {
        $blocks = [];
        foreach ((new \DOMXPath($this->parse($html)))->query('//p|//li|//td|//th|//caption|//h1|//h2|//h3|//h4|//h5|//h6') ?: [] as $block) {
            if ($block instanceof \DOMElement) {
                $blocks[] = $this->normalizeSpace($block->textContent);
            }
        }
        return $blocks;
    }

    private function visibleText(string $html): string
    {
        return $this->normalizeSpace((string)$this->parse($html)->textContent);
    }

    private function normalizeSpace(string $text): string
    {
        return trim((string)preg_replace('/[\s\x{A0}]+/u', ' ', $text));
    }

    private function parse(string $html): \DOMDocument
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $root = $document->createElement('root');
        $document->appendChild($root);
        foreach ((new HTML5(['disable_html_ns' => true]))->loadHTMLFragment($html)->childNodes as $node) {
            $root->appendChild($document->importNode($node, true));
        }
        return $document;
    }

    private function lastAnswer(): string
    {
        $exchanges = $this->exchanges->getArrayCopy();
        $exchange = end($exchanges);
        return $this->bodyOf($exchange === false ? null : $exchange['response']);
    }

    private function writeLog(string $translated): void
    {
        $file = (string)getenv('DEEPL_REAL_API_LOG');
        if ($file === '') {
            return;
        }
        foreach ($this->exchanges as $exchange) {
            $request = json_decode((string)$exchange['request']->getBody(), true);
            $response = $exchange['response'] instanceof ResponseInterface ? $exchange['response'] : null;
            $answer = json_decode($this->bodyOf($response), true);
            file_put_contents($file, json_encode([
                'test' => $this->dataName(),
                'request' => $request,
                'status' => $response?->getStatusCode(),
                'answer' => $answer ?? $this->bodyOf($response),
                'translated' => $translated,
                'sentCharacters' => array_sum(array_map(mb_strlen(...), (array)($request['text'] ?? []))),
                'billedCharacters' => array_sum(array_column((array)($answer['translations'] ?? []), 'billed_characters')),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
        }
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
