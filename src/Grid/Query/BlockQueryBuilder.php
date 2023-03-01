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
use Kaudaj\Module\Blocks\Repository\BlockGroupBlockRepository;
use Kaudaj\Module\Blocks\Repository\BlockGroupRepository;
use Kaudaj\Module\Blocks\Repository\BlockRepository;
use PrestaShop\PrestaShop\Core\Grid\Query\AbstractDoctrineQueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Query\DoctrineSearchCriteriaApplicatorInterface;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

final class BlockQueryBuilder extends AbstractDoctrineQueryBuilder
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
            ->select('b.id_block, bl.name, b.type, bgb.position')
            ->addSelect((!key_exists('group', $filters) ? "GROUP_CONCAT(bgl.name SEPARATOR ', ')" : 'bgl.name') . ' AS `groups`')
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
        $qb->select('COUNT(b.id_block)');

        return $qb;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function getQueryBuilder(array $filters): QueryBuilder
    {
        $availableFilters = [
            'id_block',
            'group',
            'name',
            'position',
            'type',
        ];

        $qb = $this->connection
            ->createQueryBuilder()
            ->from($this->dbPrefix . BlockRepository::TABLE_NAME, 'b')
            ->leftJoin(
                'b',
                $this->dbPrefix . BlockRepository::LANG_TABLE_NAME,
                'bl',
                'b.id_block = bl.id_block AND bl.id_lang = :langId'
            )
            ->setParameter('langId', $this->contextLangId)
        ;

        if (!key_exists('group', $filters)) {
            $qb
                ->leftJoin(
                    'b',
                    $this->dbPrefix . BlockGroupBlockRepository::TABLE_NAME,
                    'bgb',
                    'b.id_block = bgb.id_block'
                )
                ->groupBy('b.id_block')
            ;
        }

        foreach ($filters as $filterName => $value) {
            if (!in_array($filterName, $availableFilters, true)) {
                continue;
            }

            switch ($filterName) {
                case 'id_block':
                    $qb->andWhere('b.`' . $filterName . '` = :' . $filterName);
                    $qb->setParameter($filterName, $value);

                    break;
                case 'position':
                    $modifiedPositionFilter = $this->getModifiedPositionFilter($value);
                    $qb->andWhere('b.`' . $filterName . '` = :' . $filterName);
                    $qb->setParameter($filterName, $modifiedPositionFilter);

                    break;
                case 'name':
                    $qb->andWhere('bl.`' . $filterName . '` LIKE :' . $filterName);
                    $qb->setParameter($filterName, '%' . $value . '%');

                    break;
                case 'group':
                    $qb
                        ->innerJoin(
                            'b',
                            $this->dbPrefix . BlockGroupBlockRepository::TABLE_NAME,
                            'bgb',
                            'b.id_block = bgb.id_block AND bgb.id_block_group = :blockGroupId'
                        )
                    ;

                    $qb->setParameter('blockGroupId', $value);

                    break;
                default:
                    $qb->andWhere('b.`' . $filterName . '` LIKE :' . $filterName);
                    $qb->setParameter($filterName, '%' . $value . '%');
            }
        }

        $qb
            ->leftJoin(
                'bgb',
                $this->dbPrefix . BlockGroupRepository::LANG_TABLE_NAME,
                'bgl',
                'bgb.id_block_group = bgl.id_block_group AND bgl.id_lang = :langId'
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

        $reducedByOneFilterValue = intval($positionFilterValue) - 1;
        if (0 > $reducedByOneFilterValue) {
            return null;
        }

        return $reducedByOneFilterValue;
    }
}
