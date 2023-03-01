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
use Doctrine\ORM\Query\Expr\Join;
use Kaudaj\Module\Blocks\Entity\BlockGroup;

/**
 * @method BlockGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method BlockGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method BlockGroup[] findAll()
 * @method BlockGroup[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends EntityRepository<BlockGroup>
 */
class BlockGroupRepository extends EntityRepository
{
    public const TABLE_NAME = 'kj_blocks_block_group';
    public const TABLE_NAME_WITH_PREFIX = _DB_PREFIX_ . self::TABLE_NAME;

    public const LANG_TABLE_NAME = self::TABLE_NAME . '_lang';
    public const LANG_TABLE_NAME_WITH_PREFIX = _DB_PREFIX_ . self::LANG_TABLE_NAME;

    /**
     * @return BlockGroup[]
     */
    public function findByHook(int $hookId): array
    {
        $qb = $this->createQueryBuilder('bg');

        $qb
            ->innerJoin(
                'bg.blockGroupHooks',
                'bgh',
                Join::WITH,
                $qb->expr()->eq('bgh.hookId', ':hookId')
            )
            ->setParameter('hookId', $hookId)
            ->orderBy('bgh.position', 'ASC')
        ;

        /** @var BlockGroup[] */
        $result = $qb->getQuery()->execute();

        return $result;
    }

    public function findByName(string $name): ?BlockGroup
    {
        $qb = $this->createQueryBuilder('bg');

        $qb
            ->innerJoin(
                'bg.blockGroupLangs',
                'bgl',
                Join::WITH,
                $qb->expr()->eq('bgl.name', ':name')
            )
            ->setParameter('name', $name)
        ;

        /** @var BlockGroup|null */
        $result = $qb->getQuery()->getOneOrNullResult();

        return $result;
    }
}
