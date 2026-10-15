<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Regression\Fixtures\Classes;

use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * Records the `isImporting` flag of every DataHandler that processes a command or a record, the way a DataHandler
 * hook that skips imports reads it.
 */
final class RecordImportingStateHook
{
    /**
     * @var list<array{string, bool}>
     */
    public static array $seen = [];

    /**
     * @param int|string $id
     * @param mixed $value
     * @param mixed $pasteUpdate
     */
    public function processCmdmap_preProcess(
        string $command,
        string $table,
        $id,
        $value,
        DataHandler $dataHandler,
        $pasteUpdate
    ): void {
        self::$seen[] = [$command . ' ' . $table . ':' . $id, $dataHandler->isImporting];
    }

    /**
     * @param array<string, mixed> $incomingFieldArray
     */
    public function processDatamap_preProcessFieldArray(
        array &$incomingFieldArray,
        string $table,
        int|string $id,
        DataHandler $dataHandler
    ): void {
        self::$seen[] = ['datamap ' . $table, $dataHandler->isImporting];
    }
}
