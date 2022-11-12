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

use Hook;
use Kaudaj\Module\Blocks\Domain\Block\Exception\BlockException;
use Kaudaj\Module\Blocks\Domain\Block\Exception\BlockNotFoundException;
use Kaudaj\Module\Blocks\Domain\Block\Query\GetBlocksByHook;
use Kaudaj\Module\Blocks\Entity\Block;
use PrestaShopException;

/**
 * Class GetBlocksHandler is responsible for getting block entities.
 *
 * @internal
 */
final class GetBlocksByHookHandler extends AbstractBlockQueryHandler
{
    /**
     * @throws PrestaShopException
     * @throws BlockNotFoundException
     *
     * @return Block[]
     */
    public function handle(GetBlocksByHook $query): array
    {
        try {
            $hookId = Hook::getIdByName($query->getHookName());
            if (!$hookId) {
                return [];
            }

            $blocks = $this->entityRepository->findByHook(intval($hookId));
        } catch (PrestaShopException $e) {
            $message = 'An unexpected error occurred when retrieving blocks';

            throw new BlockException($message, 0, $e);
        }

        return $blocks;
    }
}
