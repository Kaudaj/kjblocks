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

namespace Kaudaj\Module\ContentBlocks\Domain\ContentBlock\QueryHandler;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Exception\ContentBlockNotFoundException;
use Kaudaj\Module\ContentBlocks\Entity\ContentBlock;
use PrestaShopDatabaseException;
use PrestaShopException;

/**
 * Class AbstractContentBlockQueryHandler.
 */
abstract class AbstractContentBlockQueryHandler
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ObjectRepository<ContentBlock>
     */
    protected $entityRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        /** @var ObjectRepository<ContentBlock> */
        $entityRepository = $this->entityManager->getRepository(ContentBlock::class);

        $this->entityRepository = $entityRepository;
    }

    /**
     * Gets content block entity.
     *
     * @throws ContentBlockNotFoundException
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    protected function getContentBlockEntity(int $id): ContentBlock
    {
        /** @var ContentBlock|null */
        $contentBlock = $this->entityRepository->find($id);

        if (!$contentBlock) {
            throw new ContentBlockNotFoundException();
        }

        return $contentBlock;
    }
}
