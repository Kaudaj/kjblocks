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

namespace Kaudaj\Module\ContentBlocks\Repository;

use Doctrine\ORM\EntityRepository;
use Kaudaj\Module\ContentBlocks\Entity\ContentBlockHook;

/**
 * @method ContentBlockHook|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContentBlockHook|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContentBlockHook[] findAll()
 * @method ContentBlockHook[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends EntityRepository<ContentBlockHook>
 */
class ContentBlockHookRepository extends EntityRepository
{
    public const TABLE_NAME = 'kj_content_blocks_content_block_hook';
    public const TABLE_NAME_WITH_PREFIX = _DB_PREFIX_ . self::TABLE_NAME;

    /**
     * Find highest position of content blocks for a given hook id.
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
            ->select('IDENTITY(cbh.contentBlock) as contentBlockId')
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
                ->where('cbh.contentBlock = :contentBlockId')
                ->andWhere('cbh.hookId = :hookId')
                ->setParameter('contentBlockId', $hookBlocks[$i]['contentBlockId'])
                ->setParameter('hookId', $hookId)
            ;

            $qb->getQuery()->execute();
        }
    }
}
