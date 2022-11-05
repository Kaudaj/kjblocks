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

namespace Kaudaj\Module\Blocks\Domain\Block\QueryHandler;

use Doctrine\ORM\EntityManager;
use Kaudaj\Module\Blocks\Domain\Block\Exception\BlockNotFoundException;
use Kaudaj\Module\Blocks\Entity\Block;
use Kaudaj\Module\Blocks\Repository\BlockRepository;
use PrestaShopDatabaseException;
use PrestaShopException;

/**
 * Class AbstractBlockQueryHandler.
 */
abstract class AbstractBlockQueryHandler
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var BlockRepository
     */
    protected $entityRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        /** @var BlockRepository */
        $entityRepository = $this->entityManager->getRepository(Block::class);
        $this->entityRepository = $entityRepository;
    }

    /**
     * Gets block entity.
     *
     * @throws BlockNotFoundException
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    protected function getBlockEntity(int $id): Block
    {
        /** @var Block|null */
        $block = $this->entityRepository->find($id);

        if (!$block) {
            throw new BlockNotFoundException();
        }

        return $block;
    }
}