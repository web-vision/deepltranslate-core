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
}
