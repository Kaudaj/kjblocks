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
use Doctrine\ORM\Query\Expr\Join;
use Kaudaj\Module\ContentBlocks\Entity\ContentBlock;

/**
 * @method ContentBlock|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContentBlock|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContentBlock[] findAll()
 * @method ContentBlock[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends EntityRepository<ContentBlock>
 */
class ContentBlockRepository extends EntityRepository
{
    public const TABLE_NAME = 'kj_content_blocks_content_block';
    public const TABLE_NAME_WITH_PREFIX = _DB_PREFIX_ . self::TABLE_NAME;

    public const LANG_TABLE_NAME = 'kj_content_blocks_content_block_lang';
    public const LANG_TABLE_NAME_WITH_PREFIX = _DB_PREFIX_ . self::LANG_TABLE_NAME;

    /**
     * @return ContentBlock[]
     */
    public function findByHook(int $hookId): array
    {
        $qb = $this->createQueryBuilder('cb');

        $qb
            ->innerJoin(
                'cb.contentBlockHooks',
                'cbh',
                Join::WITH,
                $qb->expr()->eq('cbh.hookId', ':hookId')
            )
            ->setParameter('hookId', $hookId)
            ->orderBy('cbh.position', 'ASC')
        ;

        /** @var ContentBlock[] */
        $result = $qb->getQuery()->execute();

        return $result;
    }
}
