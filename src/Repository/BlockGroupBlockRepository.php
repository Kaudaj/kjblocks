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
use Kaudaj\Module\Blocks\Entity\BlockGroupBlock;

/**
 * @method BlockGroupBlock|null find($id, $lockMode = null, $lockVersion = null)
 * @method BlockGroupBlock|null findOneBy(array $criteria, array $orderBy = null)
 * @method BlockGroupBlock[] findAll()
 * @method BlockGroupBlock[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends EntityRepository<BlockGroupBlock>
 */
class BlockGroupBlockRepository extends EntityRepository
{
    public const TABLE_NAME = BlockGroupRepository::TABLE_NAME . '_block';
    public const TABLE_NAME_WITH_PREFIX = _DB_PREFIX_ . self::TABLE_NAME;

    /**
     * Find highest position of block groups for a given hook id.
     * Returns -1 if there is no block groups.
     */
    public function findMaxPosition(int $blockGroupId): int
    {
        $qb = $this->createQueryBuilder('bgb')
            ->select('MAX(bgb.position)')
            ->where('bgb.blockGroup = :blockGroupId')
            ->setParameter('blockGroupId', $blockGroupId)
        ;

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result !== null ? intval($result) : -1;
    }

    /**
     * Set accurate positions after moving or deleting a block group
     */
    public function cleanPositions(int $blockGroupId): void
    {
        $qb = $this->createQueryBuilder('bgb')
            ->select('IDENTITY(bgb.block) as blockId')
            ->where('bgb.blockGroup = :blockGroupId')
            ->setParameter('blockGroupId', $blockGroupId)
            ->orderBy('bgb.block')
        ;

        $blockGroupBlocks = $qb->getQuery()->getResult();
        if (!is_array($blockGroupBlocks)) {
            return;
        }

        for ($i = 0; $i < count($blockGroupBlocks); ++$i) {
            $qb = $this->createQueryBuilder('bgb')
                ->update()
                ->set('bgb.position', $i)
                ->where('bgb.block = :blockId')
                ->andWhere('bgb.blockGroup = :blockGroupId')
                ->setParameter('blockId', $blockGroupBlocks[$i]['blockId'])
                ->setParameter('blockGroupId', $blockGroupId)
            ;

            $qb->getQuery()->execute();
        }
    }
}
