<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WebVision\Deepltranslate\Core\Domain\Enum\ContentFormat;
use WebVision\Deepltranslate\Core\Service\ContentFormatResolver;
use WebVision\Deepltranslate\Core\Tests\Functional\AbstractDeepLTestCase;

#[CoversClass(ContentFormatResolver::class)]
final class ContentFormatResolverTest extends AbstractDeepLTestCase
{
    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = __DIR__ . '/../Fixtures/Extensions/test_inline_relations';
        parent::setUp();
    }

    public static function resolveDataProvider(): \Generator
    {
        yield 'rich text enabled by columnsOverrides of the record type' => [
            'table' => 'tt_content',
            'field' => 'bodytext',
            'record' => [
                'uid' => 1,
                'CType' => 'text',
            ],
            'expected' => ContentFormat::RichText,
        ];
        yield 'same column without rich text in another record type' => [
            'table' => 'tt_content',
            'field' => 'bodytext',
            'record' => [
                'uid' => 1,
                'CType' => 'header',
            ],
            'expected' => ContentFormat::PlainText,
        ];
        yield 'HTML in a code editor field of the record type' => [
            'table' => 'tt_content',
            'field' => 'bodytext',
            'record' => [
                'uid' => 1,
                'CType' => 'html',
            ],
            'expected' => ContentFormat::RichText,
        ];
        yield 'TypoScript in a code editor field' => [
            'table' => 'pages',
            'field' => 'TSconfig',
            'record' => [
                'uid' => 1,
                'doktype' => 1,
            ],
            'expected' => ContentFormat::Code,
        ];
        yield 'text field of a record which is no inline child' => [
            'table' => 'tx_testinlinerelations_child_declared',
            'field' => 'description',
            'record' => [
                'uid' => 0,
            ],
            'expected' => ContentFormat::PlainText,
        ];
        yield 'input field' => [
            'table' => 'tt_content',
            'field' => 'header',
            'record' => [
                'uid' => 1,
                'CType' => 'text',
            ],
            'expected' => ContentFormat::PlainText,
        ];
        yield 'rich text enabled on the column' => [
            'table' => 'sys_news',
            'field' => 'content',
            'record' => [
                'uid' => 1,
            ],
            'expected' => ContentFormat::RichText,
        ];
        yield 'unknown field' => [
            'table' => 'tt_content',
            'field' => 'not_existing',
            'record' => [
                'uid' => 1,
                'CType' => 'text',
            ],
            'expected' => ContentFormat::Unknown,
        ];
        yield 'no field given' => [
            'table' => 'tt_content',
            'field' => '',
            'record' => [
                'uid' => 1,
                'CType' => 'text',
            ],
            'expected' => ContentFormat::Unknown,
        ];
        yield 'unknown table' => [
            'table' => 'tx_not_existing',
            'field' => 'title',
            'record' => [
                'uid' => 1,
            ],
            'expected' => ContentFormat::Unknown,
        ];
    }

    /**
     * @param array<string, mixed> $record
     */
    #[Test]
    #[DataProvider('resolveDataProvider')]
    public function resolveReturnsFormatOfTheField(string $table, string $field, array $record, ContentFormat $expected): void
    {
        $this->assertSame($expected, $this->get(ContentFormatResolver::class)->resolve($table, $field, $record));
    }

    #[Test]
    public function resolveReturnsRichTextEnabledByOverrideChildTcaOfTheInlineParent(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Regression/Fixtures/inlineRelationsParentLocalize.csv');
        $subject = $this->get(ContentFormatResolver::class);

        $this->assertSame(
            ContentFormat::RichText,
            $subject->resolve('tx_testinlinerelations_child_declared', 'description', ['uid' => 1, 'parentid' => 1])
        );
        $this->assertSame(
            ContentFormat::PlainText,
            $subject->resolve('tx_testinlinerelations_child_declared', 'title', ['uid' => 1, 'parentid' => 1])
        );
    }

    public static function overrideChildTcaSwitchingRichTextOffDataProvider(): \Generator
    {
        yield 'rich text switched off' => [
            'override' => ['enableRichtext' => false],
            'expected' => ContentFormat::PlainText,
        ];
        yield 'TypoScript in a code editor' => [
            'override' => ['renderType' => 'codeEditor', 'format' => 'typoscript'],
            'expected' => ContentFormat::Code,
        ];
    }

    /**
     * FormEngine shows the field as the `overrideChildTca` of the inline parent field configures it, also when that
     * switches the rich text editor of the column off.
     *
     * @param array<string, mixed> $override
     */
    #[Test]
    #[DataProvider('overrideChildTcaSwitchingRichTextOffDataProvider')]
    public function resolveReturnsFormatOfOverrideChildTcaSwitchingRichTextOff(array $override, ContentFormat $expected): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Regression/Fixtures/inlineRelationsParentLocalize.csv');
        $GLOBALS['TCA']['tx_testinlinerelations_child_declared']['columns']['description']['config']['enableRichtext'] = true;
        $GLOBALS['TCA']['tx_testinlinerelations_parent']['columns']['children_declared']['config']['overrideChildTca']['columns']['description']['config'] = $override;
        $subject = $this->get(ContentFormatResolver::class);

        $this->assertSame($expected, $subject->resolve('tx_testinlinerelations_child_declared', 'description', ['uid' => 1, 'parentid' => 1]));
        // Without the parent, the column decides.
        $this->assertSame(ContentFormat::RichText, $subject->resolve('tx_testinlinerelations_child_declared', 'description', ['uid' => 0]));
    }

    /**
     * FormEngine applies the `columnsOverrides` of the record type after the `overrideChildTca` of the parent
     * field (`InlineOverrideChildTca` before `TcaColumnsOverrides`), so the record type has the last word.
     */
    #[Test]
    public function resolveAppliesColumnsOverridesOfTheRecordTypeAfterOverrideChildTca(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Regression/Fixtures/inlineRelationsParentLocalize.csv');
        $GLOBALS['TCA']['tx_testinlinerelations_child_declared']['types']['0']['columnsOverrides']['description']['config']['enableRichtext'] = false;

        $this->assertSame(
            ContentFormat::PlainText,
            $this->get(ContentFormatResolver::class)->resolve('tx_testinlinerelations_child_declared', 'description', ['uid' => 1, 'parentid' => 1])
        );
    }
}
