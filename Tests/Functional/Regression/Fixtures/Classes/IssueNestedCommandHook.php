<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Tests\Functional\Regression\Fixtures\Classes;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Issues a `deepltranslate` command for the parent record 10 on another page when the first translation of an inline
 * child is created, while the parent record of that child is still being localized. `web-vision/deepltranslate-auto-renew`
 * issues such commands from its DataHandler hooks.
 */
final class IssueNestedCommandHook
{
    private const NESTED_TABLE = 'tx_testinlinerelations_parent';
    private const NESTED_UID = 10;

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
        if ($status !== 'new' || $table !== 'tx_testinlinerelations_child_declared') {
            return;
        }
        $uid = (int)($dataHandler->substNEWwithIDs[$id] ?? $id);
        $record = BackendUtility::getRecord($table, $uid);
        if ($record === null || (int)$record['sys_language_uid'] === 0) {
            return;
        }
        $language = (int)$record['sys_language_uid'];
        $translations = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::NESTED_TABLE)
            ->count('uid', self::NESTED_TABLE, ['l10n_parent' => self::NESTED_UID, 'sys_language_uid' => $language]);
        if ($translations > 0) {
            return;
        }
        $nestedDataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $nestedDataHandler->start([], [
            self::NESTED_TABLE => [
                self::NESTED_UID => [
                    'deepltranslate' => $language,
                ],
            ],
        ]);
        $nestedDataHandler->process_cmdmap();
        if ($nestedDataHandler->errorLog !== []) {
            throw new \RuntimeException('Nested command failed: ' . implode(' | ', $nestedDataHandler->errorLog), 1792060000);
        }
    }
}
