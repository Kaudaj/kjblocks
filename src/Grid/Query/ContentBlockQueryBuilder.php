<?php
/**
 * Copyright since 2019 Kaudaj
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@kaudaj.com so we can send you a copy immediately.
 *
 * @author    Kaudaj <info@kaudaj.com>
 * @copyright Since 2019 Kaudaj
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

declare(strict_types=1);

namespace Kaudaj\Module\ContentBlocks\Grid\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Kaudaj\Module\ContentBlocks\Repository\ContentBlockLangRepository;
use Kaudaj\Module\ContentBlocks\Repository\ContentBlockRepository;
use PrestaShop\PrestaShop\Core\Grid\Query\AbstractDoctrineQueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Query\DoctrineSearchCriteriaApplicatorInterface;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

final class ContentBlockQueryBuilder extends AbstractDoctrineQueryBuilder
{
    /**
     * @var DoctrineSearchCriteriaApplicatorInterface
     */
    private $searchCriteriaApplicator;

    /**
     * @var int
     */
    private $contextLangId;

    public function __construct(
        Connection $connection,
        string $dbPrefix,
        DoctrineSearchCriteriaApplicatorInterface $searchCriteriaApplicator,
        int $contextLangId
    ) {
        parent::__construct($connection, $dbPrefix);

        $this->searchCriteriaApplicator = $searchCriteriaApplicator;
        $this->contextLangId = $contextLangId;
    }

    public function getSearchQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        $qb = $this->getQueryBuilder($searchCriteria->getFilters());
        $qb
            ->select('cb.id_content_block, h.name AS hook, cbl.name, cb.position')
            ->addSelect('cb.id_hook')
        ;

        $this->searchCriteriaApplicator
            ->applyPagination($searchCriteria, $qb)
            ->applySorting($searchCriteria, $qb)
        ;

        return $qb;
    }

    public function getCountQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        $qb = $this->getQueryBuilder($searchCriteria->getFilters());
        $qb->select('COUNT(cb.id_content_block)');

        return $qb;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function getQueryBuilder(array $filters): QueryBuilder
    {
        $availableFilters = [
            'id_content_block',
            'hook',
            'name',
            'position',
        ];

        $qb = $this->connection
            ->createQueryBuilder()
            ->from($this->dbPrefix . ContentBlockRepository::TABLE_NAME, 'cb')
            ->leftJoin(
                'cb',
                $this->dbPrefix . 'hook',
                'h',
                'cb.id_hook = h.id_hook'
            )
            ->leftJoin(
                'cb',
                $this->dbPrefix . ContentBlockLangRepository::TABLE_NAME,
                'cbl',
                'cb.id_content_block = cbl.id_content_block'
            )
            ->andWhere('cbl.id_lang = :langId')
            ->setParameter('langId', $this->contextLangId)
        ;

        foreach ($filters as $filterName => $value) {
            if (!in_array($filterName, $availableFilters, true)) {
                continue;
            }

            switch ($filterName) {
                case 'id_configurator_step':
                    $qb->andWhere('cb.`' . $filterName . '` = :' . $filterName);
                    $qb->setParameter($filterName, $value);

                    break;
                case 'position':
                    $modifiedPositionFilter = $this->getModifiedPositionFilter($value);
                    $qb->andWhere('cb.`' . $filterName . '` = :' . $filterName);
                    $qb->setParameter($filterName, $modifiedPositionFilter);

                    break;
                case 'name':
                    $qb->andWhere('cbl.`' . $filterName . '` LIKE :' . $filterName);
                    $qb->setParameter($filterName, '%' . $value . '%');

                    break;
                case 'hook':
                    $qb->andWhere('cb.`id_hook` = :' . $filterName);
                    $qb->setParameter($filterName, $value);

                    break;
                default:
                    $qb->andWhere('cb.`' . $filterName . '` LIKE :' . $filterName);
                    $qb->setParameter($filterName, '%' . $value . '%');
            }
        }

        return $qb;
    }

    /**
     * Gets modified position filter value. This is required due to in database position filter index starts from 0 and
     * for the customer which wants to filter results the value starts from 1 instead.
     *
     * @param mixed $positionFilterValue
     *
     * @return int|null - if null is returned then no results are found since position field does not hold null values
     */
    private function getModifiedPositionFilter($positionFilterValue)
    {
        if (!is_numeric($positionFilterValue)) {
            return null;
        }

        $reducedByOneFilterValue = intval($positionFilterValue) - 1;
        if (0 > $reducedByOneFilterValue) {
            return null;
        }

        return $reducedByOneFilterValue;
    }
}
