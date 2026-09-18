<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\RealApi;

use Masterminds\HTML5;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;
use WebVision\Deepltranslate\Core\Domain\Dto\TranslateContext;
use WebVision\Deepltranslate\Core\Domain\Enum\ContentFormat;
use WebVision\Deepltranslate\Core\Service\DeeplService;
use WebVision\Deepltranslate\Core\Service\ProcessingInstruction;

/**
 * Sends rich text markup through the real translation path to the real DeepL API.
 *
 * Every run is billed per character, so the group is excluded from `runTests.sh -s functional` and CI. Run it
 * locally with your own API key:
 *
 * ```
 * DEEPL_AUTH_KEY=<key> Build/Scripts/runTests.sh -s functionalDeepLApi
 * ```
 */
#[Group('deepl-real-api')]
final class RichTextTagHandlingTest extends FunctionalTestCase
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

    protected function setUp(): void
    {
        $authKey = (string)getenv('DEEPL_AUTH_KEY');
        if ($authKey === '' || getenv('DEEPL_MOCK_SERVER_PORT') !== false) {
            $this->markTestSkipped('Requires a real DeepL API key in DEEPL_AUTH_KEY and no DeepL mock server.');
        }
        parent::setUp();
        // Set at runtime only, so the key is never written into the settings file of the test instance.
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['deepltranslate_core']['apiKey'] = $authKey;
        unset($GLOBALS['DEEPL_TESTING']);
        $this->get(ProcessingInstruction::class)->setProcessingInstruction(null, null, true);
    }

    /**
     * @see https://github.com/web-vision/deepltranslate-core/issues/665
     */
    #[Test]
    public function adjacentLinksAreKeptSeparate(): void
    {
        $translateContext = new TranslateContext(
            '<p>' . "\n"
            . '    <a class="button button--whatsapp" href="https://api.whatsapp.com/send?phone=123456789" title="Ferrari 296 GTB mieten Dubai Whatsapp ">Whatsapp </a>'
            . '<a class="button button--call" href="tel:+971543946661" title="Ferrari 296 GTB mieten Dubai Anruf">Call</a>'
            . '<a class="button button--offerttool" href="t3://page?uid=407" title="Ferrari 296 GTB mieten Dubai Buchen">Book</a>' . "\n"
            . '</p>'
        );
        $translateContext->setSourceLanguageCode('DE');
        $translateContext->setContentFormat(ContentFormat::RichText);
        $translateContext->setTargetLanguageCode('EN-GB');

        $translated = $this->get(DeeplService::class)->translateContent($translateContext);

        $links = [];
        $fragment = (new HTML5(['disable_html_ns' => true]))->loadHTMLFragment($translated);
        foreach ($fragment->childNodes as $node) {
            if ($node instanceof \DOMElement) {
                foreach ($node->getElementsByTagName('a') as $link) {
                    $links[$link->getAttribute('href')] = trim($link->textContent);
                }
            }
        }
        $this->assertSame(
            [
                'https://api.whatsapp.com/send?phone=123456789',
                'tel:+971543946661',
                't3://page?uid=407',
            ],
            array_keys($links),
            $translated
        );
        foreach ($links as $href => $text) {
            $this->assertNotSame('', $text, sprintf('Link "%s" has no text in: %s', $href, $translated));
        }
    }

    /**
     * @see https://github.com/web-vision/deepltranslate-core/issues/642
     */
    #[Test]
    public function linesAfterLineBreaksGetNoLeadingPunctuation(): void
    {
        $translateContext = new TranslateContext(
            '<p>' . "\n"
            . '    Postanschrift:<br>' . "\n"
            . '    Postfach 1234<br>' . "\n"
            . '    12345 Musterstadt<br>' . "\n"
            . '    <br>' . "\n"
            . '    Büroanschrift:<br>' . "\n"
            . '    Mustergebäude<br>' . "\n"
            . '    Musterstraße 1<br>' . "\n"
            . '    12345 Musterstadt<br>' . "\n"
            . '</p>'
        );
        $translateContext->setSourceLanguageCode('DE');
        $translateContext->setContentFormat(ContentFormat::RichText);
        $translateContext->setTargetLanguageCode('ES');

        $translated = $this->get(DeeplService::class)->translateContent($translateContext);

        $lineBreaks = [];
        $fragment = (new HTML5(['disable_html_ns' => true]))->loadHTMLFragment($translated);
        foreach ($fragment->childNodes as $node) {
            if ($node instanceof \DOMElement) {
                foreach ($node->getElementsByTagName('br') as $lineBreak) {
                    $lineBreaks[] = $lineBreak;
                }
            }
        }
        $this->assertCount(8, $lineBreaks, $translated);
        foreach ($lineBreaks as $lineBreak) {
            $line = ltrim((string)$lineBreak->nextSibling?->textContent);
            $this->assertStringStartsNotWith(',', $line, $translated);
            $this->assertStringStartsNotWith('.', $line, $translated);
        }
    }
}
