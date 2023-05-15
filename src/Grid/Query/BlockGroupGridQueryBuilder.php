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

namespace Kaudaj\Module\Blocks\Grid\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Kaudaj\Module\Blocks\Repository\BlockGroupHookRepository;
use Kaudaj\Module\Blocks\Repository\BlockGroupRepository;
use PrestaShop\PrestaShop\Core\Grid\Query\AbstractDoctrineQueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Query\DoctrineSearchCriteriaApplicatorInterface;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

final class BlockGroupGridQueryBuilder extends AbstractDoctrineQueryBuilder
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
        $filters = $searchCriteria->getFilters();

        $qb = $this->getQueryBuilder($filters);
        $qb
            ->select('bg.id_block_group, bgl.name, bgh.position')
            ->addSelect((!key_exists('hook', $filters) ? "GROUP_CONCAT(h.name SEPARATOR ', ')" : 'h.name') . ' AS hooks')
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
        $qb->select('COUNT(bg.id_block_group)');

        return $qb;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function getQueryBuilder(array $filters): QueryBuilder
    {
        $availableFilters = [
            'id_block_group',
            'hook',
            'name',
            'position',
        ];

        $qb = $this->connection
            ->createQueryBuilder()
            ->from($this->dbPrefix . BlockGroupRepository::TABLE_NAME, 'bg')
            ->leftJoin(
                'bg',
                $this->dbPrefix . BlockGroupRepository::LANG_TABLE_NAME,
                'bgl',
                'bg.id_block_group = bgl.id_block_group AND bgl.id_lang = :langId'
            )
            ->setParameter('langId', $this->contextLangId)
        ;

        if (!key_exists('hook', $filters)) {
            $qb
                ->leftJoin(
                    'bg',
                    $this->dbPrefix . BlockGroupHookRepository::TABLE_NAME,
                    'bgh',
                    'bg.id_block_group = bgh.id_block_group'
                )
                ->groupBy('bg.id_block_group')
            ;
        }

        foreach ($filters as $filterName => $value) {
            if (!in_array($filterName, $availableFilters, true)) {
                continue;
            }

            switch ($filterName) {
                case 'id_block_group':
                    $qb->andWhere('bg.`' . $filterName . '` = :' . $filterName);
                    $qb->setParameter($filterName, $value);

                    break;
                case 'position':
                    $modifiedPositionFilter = $this->getModifiedPositionFilter($value);
                    $qb->andWhere('bg.`' . $filterName . '` = :' . $filterName);
                    $qb->setParameter($filterName, $modifiedPositionFilter);

                    break;
                case 'name':
                    $qb->andWhere('bgl.`' . $filterName . '` LIKE :' . $filterName);
                    $qb->setParameter($filterName, '%' . $value . '%');

                    break;
                case 'hook':
                    $qb
                        ->innerJoin(
                            'bg',
                            $this->dbPrefix . BlockGroupHookRepository::TABLE_NAME,
                            'bgh',
                            'bg.id_block_group = bgh.id_block_group AND bgh.id_hook = :hookId'
                        )
                    ;

                    $qb->setParameter('hookId', $value);

                    break;
                default:
                    $qb->andWhere('bg.`' . $filterName . '` LIKE :' . $filterName);
                    $qb->setParameter($filterName, '%' . $value . '%');
            }
        }

        $qb
            ->leftJoin(
                'bgh',
                $this->dbPrefix . 'hook',
                'h',
                'bgh.id_hook = h.id_hook'
            )
        ;

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

        $reducedByOneFilterValue = (int) $positionFilterValue - 1;
        if (0 > $reducedByOneFilterValue) {
            return null;
        }

        return $reducedByOneFilterValue;
    }
}
