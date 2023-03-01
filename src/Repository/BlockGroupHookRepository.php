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

namespace Kaudaj\Module\Blocks\Repository;

use Doctrine\ORM\EntityRepository;
use Kaudaj\Module\Blocks\Entity\BlockGroupHook;

/**
 * @method BlockGroupHook|null find($id, $lockMode = null, $lockVersion = null)
 * @method BlockGroupHook|null findOneBy(array $criteria, array $orderBy = null)
 * @method BlockGroupHook[] findAll()
 * @method BlockGroupHook[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends EntityRepository<BlockGroupHook>
 */
class BlockGroupHookRepository extends EntityRepository
{
    public const TABLE_NAME = BlockGroupRepository::TABLE_NAME . '_hook';
    public const TABLE_NAME_WITH_PREFIX = _DB_PREFIX_ . self::TABLE_NAME;

    /**
     * Find highest position of block groups for a given hook id.
     * Returns -1 if there is no block groups.
     */
    public function findMaxPosition(int $hookId): int
    {
        $qb = $this->createQueryBuilder('bgh')
            ->select('MAX(bgh.position)')
            ->where('bgh.hookId = :hookId')
            ->setParameter('hookId', $hookId)
        ;

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result !== null ? intval($result) : -1;
    }

    /**
     * Set accurate positions after moving or deleting a block group
     */
    public function cleanPositions(int $hookId): void
    {
        $qb = $this->createQueryBuilder('bgh')
            ->select('IDENTITY(bgh.blockGroup) as blockGroupId')
            ->where('bgh.hookId = :hookId')
            ->setParameter('hookId', $hookId)
            ->orderBy('bgh.hookId')
        ;

        $hookBlockGroups = $qb->getQuery()->getResult();
        if (!is_array($hookBlockGroups)) {
            return;
        }

        for ($i = 0; $i < count($hookBlockGroups); ++$i) {
            $qb = $this->createQueryBuilder('bgh')
                ->update()
                ->set('bgh.position', $i)
                ->where('bgh.blockGroup = :blockGroupId')
                ->andWhere('bgh.hookId = :hookId')
                ->setParameter('blockGroupId', $hookBlockGroups[$i]['blockGroupId'])
                ->setParameter('hookId', $hookId)
            ;

            $qb->getQuery()->execute();
        }
    }
}
