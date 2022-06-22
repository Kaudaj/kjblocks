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
use Kaudaj\Module\ContentBlocks\Entity\ContentBlockLang;

/**
 * @method ContentBlockLang|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContentBlockLang|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContentBlockLang[] findAll()
 * @method ContentBlockLang[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends EntityRepository<ContentBlockLang>
 */
class ContentBlockLangRepository extends EntityRepository
{
    public const TABLE_NAME = 'kj_content_blocks_content_block_lang';
    public const TABLE_NAME_WITH_PREFIX = _DB_PREFIX_ . self::TABLE_NAME;
}
