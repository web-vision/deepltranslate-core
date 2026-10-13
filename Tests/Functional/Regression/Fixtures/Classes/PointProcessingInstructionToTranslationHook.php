<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Regression\Fixtures\Classes;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\Deepltranslate\Core\Service\ProcessingInstruction;

/**
 * Points the processing instruction to every created translation of an inline child or grandchild while the parent
 * record is still being localized, like the `deepltranslate` command `web-vision/deepltranslate-auto-renew` issues
 * for an inline child translation.
 */
final class PointProcessingInstructionToTranslationHook
{
    private const TABLES = [
        'tx_testinlinerelations_child_declared',
        'tx_testinlinerelations_grandchild',
    ];

    /**
     * @param array<string, mixed> $fieldArray
     */
    public function processDatamap_afterDatabaseOperations(
        string $status,
        string $table,
        string|int $id,
        array $fieldArray,
        DataHandler $dataHandler
    ): void {
        if ($status !== 'new' || !in_array($table, self::TABLES, true)) {
            return;
        }
        $uid = (int)($dataHandler->substNEWwithIDs[$id] ?? $id);
        $record = BackendUtility::getRecord($table, $uid);
        if ($record === null || (int)$record['sys_language_uid'] === 0) {
            return;
        }
        GeneralUtility::makeInstance(ProcessingInstruction::class)->setProcessingInstruction($table, $uid, true);
    }
}
