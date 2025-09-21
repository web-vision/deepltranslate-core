<?php

declare(strict_types=1);

namespace WebVision\Deepltranslate\Core\Domain\Repository;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Exception as DBALException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal cloned from TYPO3 due to deprecation in TYPO3 v11 and removal in TYPO3 v12.
 * @todo Find a suitable replacement in TYPO3 core to remove this maintenance burden.
 */
final class PageTreeRepository
{
    /**
     * Recursively fetch all descendants of a given page
     *
     * Copied from deprecated class QueryGenerator
     * @see \TYPO3\CMS\Core\Database\QueryGenerator::getTreeList()
     *
     * @throws Exception
     * @throws DBALException
     */
    public function getTreeList(int $id, int $depth, int $begin = 0, string $permClause = ''): string
    {
        if ($id < 0) {
            $id = abs($id);
        }
        if ($begin == 0) {
            $theList = (string)$id;
        } else {
            $theList = '';
        }
        if ($id && $depth > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $queryBuilder->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('sys_language_uid', 0)
                )
                ->orderBy('uid');
            if ($permClause !== '') {
                $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($permClause));
            }
            $statement = $queryBuilder->executeQuery();
            while ($row = $statement->fetchAssociative()) {
                if ($begin <= 0) {
                    $theList .= ',' . $row['uid'];
                }
                if ($depth > 1) {
                    $theSubList = $this->getTreeList($row['uid'], $depth - 1, $begin - 1, $permClause);
                    if (!empty($theList) && !empty($theSubList) && ($theSubList[0] !== ',')) {
                        $theList .= ',';
                    }
                    $theList .= $theSubList;
                }
            }
        }
        return $theList;
    }
}
