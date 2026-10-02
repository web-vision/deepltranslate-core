<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Unit\Service;

use Masterminds\HTML5;
use Masterminds\HTML5\Serializer\OutputRules;
use Masterminds\HTML5\Serializer\RulesInterface;
use Masterminds\HTML5\Serializer\Traverser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WebVision\Deepltranslate\Core\Service\RichTextHtml5;
use WebVision\Deepltranslate\Core\Service\RichTextOutputRules;

/**
 * {@see RichTextHtml5} copies `HTML5::save()` and {@see RichTextOutputRules} overrides protected methods of
 * masterminds/html5. `composer.json` allows `^2.10.0` like the TYPO3 core, these tests fail when a release
 * changes what the subclasses rely on.
 */
#[CoversClass(RichTextHtml5::class)]
#[CoversClass(RichTextOutputRules::class)]
final class RichTextHtml5Test extends UnitTestCase
{
    /**
     * Fingerprints of the masterminds/html5 methods the subclasses copy, the same in 2.10.0, 2.10.1 and 2.11.0.
     * If one changes, compare the copy with the new method and update the fingerprint.
     */
    private const COPIED_METHODS = [
        HTML5::class . '::save' => 'ff64597b27875fd666150e8a67590fe76fd73637',
        OutputRules::class . '::openTag' => '0cb6e1234b500ae7873c0b7a6f93883bc6695c07',
    ];

    #[Test]
    public function copiedMethodsOfMastermindsHtml5AreUnchanged(): void
    {
        foreach (self::COPIED_METHODS as $method => $fingerprint) {
            [$class, $name] = explode('::', $method, 2);
            $reflection = new \ReflectionMethod($class, $name);
            $lines = array_slice(
                (array)file((string)$reflection->getFileName()),
                (int)$reflection->getStartLine() - 1,
                (int)$reflection->getEndLine() - (int)$reflection->getStartLine() + 1
            );
            $this->assertSame(
                $fingerprint,
                sha1((string)preg_replace('/\s+/', ' ', implode('', $lines))),
                sprintf('%s of masterminds/html5 changed, compare the copy in the subclass with it.', $method)
            );
        }
    }

    #[Test]
    public function internalsOfMastermindsHtml5UsedByTheSubclassesExist(): void
    {
        $methods = [
            [OutputRules::class, 'attrs', 1],
            [OutputRules::class, 'openTag', 1],
            [OutputRules::class, 'escape', 2],
            [OutputRules::class, 'namespaceAttrs', 1],
            [OutputRules::class, 'wr', 1],
        ];
        foreach ($methods as [$class, $name, $parameters]) {
            $method = new \ReflectionMethod($class, $name);
            $this->assertTrue($method->isProtected(), $class . '::' . $name);
            $this->assertSame($parameters, $method->getNumberOfParameters(), $class . '::' . $name);
        }
        foreach (['outputMode', 'traverser'] as $property) {
            $this->assertTrue((new \ReflectionProperty(OutputRules::class, $property))->isProtected(), $property);
        }
        $this->assertTrue((new \ReflectionMethod(OutputRules::class, 'unsetTraverser'))->isPublic());
        $this->assertTrue((new \ReflectionClassConstant(OutputRules::class, 'IM_IN_HTML'))->isPublic());
        $this->assertSame(
            ['dom', 'out', 'rules', 'options'],
            array_map(
                static fn(\ReflectionParameter $parameter): string => $parameter->getName(),
                (new \ReflectionMethod(Traverser::class, '__construct'))->getParameters()
            )
        );
        $this->assertTrue((new \ReflectionClass(RichTextOutputRules::class))->implementsInterface(RulesInterface::class));
        $this->assertTrue((new \ReflectionMethod(HTML5::class, 'getOptions'))->isPublic());
    }

    /**
     * Content the output rules do not change is written exactly as masterminds/html5 writes it, options included.
     */
    #[Test]
    public function htmlIsWrittenLikeMastermindsHtml5WhereTheOutputRulesDoNotApply(): void
    {
        $html = '<p class="a" data-x="1 &amp; 2">Text &amp; <strong>more</strong>&nbsp;<a href="?a=1&amp;b=2" title="&quot;q&quot;">link</a></p>'
            . '<!-- note --><svg viewBox="0 0 1 1"><path d="M0 0"></path></svg><script>if (a < b && c) {}</script><pre>  x</pre>';
        foreach ([[], ['encode_entities' => true]] as $options) {
            $mastermindsHtml5 = new HTML5(['disable_html_ns' => true]);
            $subject = new RichTextHtml5(['disable_html_ns' => true]);
            $fragment = $mastermindsHtml5->loadHTMLFragment($html);

            $this->assertSame($mastermindsHtml5->saveHTML($fragment, $options), $subject->saveHTML($fragment, $options));
        }
    }

    #[Test]
    public function htmlIsWrittenTheWayTypo3StoresRichText(): void
    {
        $subject = new RichTextHtml5(['disable_html_ns' => true]);
        $fragment = $subject->loadHTMLFragment('<p title="a &gt; b &lt; c">x<br>y</p><hr><img src="x.jpg" alt="A"><svg><path d="M0 0"/></svg>');

        $this->assertSame(
            '<p title="a &gt; b &lt; c">x<br />y</p><hr /><img src="x.jpg" alt="A" /><svg><path d="M0 0" /></svg>',
            $subject->saveHTML($fragment)
        );
    }
}
