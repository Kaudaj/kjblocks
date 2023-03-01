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

use Kaudaj\Module\Blocks\Domain\BlockGroup\Exception\BlockGroupException;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Exception\BlockGroupNotFoundException;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Query\GetBlockGroupsByHook;
use Kaudaj\Module\Blocks\Entity\BlockGroup;

/**
 * @internal
 */
final class GetBlockGroupsByHookHandler extends AbstractBlockGroupQueryHandler
{
    /**
     * @return BlockGroup[]
     *
     * @throws \PrestaShopException
     * @throws BlockGroupNotFoundException
     */
    public function handle(GetBlockGroupsByHook $query): array
    {
        try {
            $hookId = \Hook::getIdByName($query->getHookName());
            if (!$hookId) {
                return [];
            }

            $blockGroups = $this->entityRepository->findByHook(intval($hookId));
        } catch (\PrestaShopException $e) {
            $message = 'An unexpected error occurred when retrieving block groups';

            throw new BlockGroupException($message, 0, $e);
        }

        return $blockGroups;
    }
}
