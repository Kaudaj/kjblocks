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

namespace Kaudaj\Module\Blocks\Domain\BlockGroup\QueryHandler;

use Doctrine\ORM\EntityManager;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Exception\BlockGroupNotFoundException;
use Kaudaj\Module\Blocks\Entity\BlockGroup;
use Kaudaj\Module\Blocks\Repository\BlockGroupRepository;

abstract class AbstractBlockGroupQueryHandler
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var BlockGroupRepository
     */
    protected $entityRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        /** @var BlockGroupRepository */
        $entityRepository = $this->entityManager->getRepository(BlockGroup::class);
        $this->entityRepository = $entityRepository;
    }

    /**
     * @throws BlockGroupNotFoundException
     * @throws \PrestaShopException
     * @throws \PrestaShopDatabaseException
     */
    protected function getBlockGroupEntity(int $id): BlockGroup
    {
        /** @var BlockGroup|null */
        $blockGroup = $this->entityRepository->find($id);

        if (!$blockGroup) {
            throw new BlockGroupNotFoundException();
        }

        return $blockGroup;
    }
}
