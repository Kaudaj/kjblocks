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
use Kaudaj\Module\Blocks\Entity\BlockHook;

/**
 * @method BlockHook|null find($id, $lockMode = null, $lockVersion = null)
 * @method BlockHook|null findOneBy(array $criteria, array $orderBy = null)
 * @method BlockHook[] findAll()
 * @method BlockHook[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends EntityRepository<BlockHook>
 */
class BlockHookRepository extends EntityRepository
{
    public const TABLE_NAME = 'kj_blocks_block_hook';
    public const TABLE_NAME_WITH_PREFIX = _DB_PREFIX_ . self::TABLE_NAME;

    /**
     * Find highest position of blocks for a given hook id.
     * Returns -1 if there is no blocks.
     */
    public function findMaxPosition(int $hookId): int
    {
        $qb = $this->createQueryBuilder('cbh')
            ->select('MAX(cbh.position)')
            ->where('cbh.hookId = :hookId')
            ->setParameter('hookId', $hookId)
        ;

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result !== null ? intval($result) : -1;
    }

    /**
     * Set accurate positions after moving or deleting a block
     */
    public function cleanPositions(int $hookId): void
    {
        $qb = $this->createQueryBuilder('cbh')
            ->select('IDENTITY(cbh.block) as blockId')
            ->where('cbh.hookId = :hookId')
            ->setParameter('hookId', $hookId)
            ->orderBy('cbh.hookId')
        ;

        $hookBlocks = $qb->getQuery()->getResult();
        if (!is_array($hookBlocks)) {
            return;
        }

        for ($i = 0; $i < count($hookBlocks); ++$i) {
            $qb = $this->createQueryBuilder('cbh')
                ->update()
                ->set('cbh.position', $i)
                ->where('cbh.block = :blockId')
                ->andWhere('cbh.hookId = :hookId')
                ->setParameter('blockId', $hookBlocks[$i]['blockId'])
                ->setParameter('hookId', $hookId)
            ;

            $qb->getQuery()->execute();
        }
    }
}
