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

namespace Kaudaj\Module\Blocks\Domain\BlockGroup\CommandHandler;

use Kaudaj\Module\Blocks\Domain\BlockGroup\Command\DeleteBlockGroupCommand;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Exception\BlockGroupException;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Exception\CannotDeleteBlockGroupException;

/**
 * @internal
 */
final class DeleteBlockGroupHandler extends AbstractBlockGroupCommandHandler
{
    /**
     * @throws BlockGroupException
     */
    public function handle(DeleteBlockGroupCommand $command): void
    {
        $blockGroupId = $command->getBlockGroupId()->getValue();

        $blockGroup = $this->getBlockGroupEntity($blockGroupId);

        try {
            $blockGroupHooks = $blockGroup->getBlockGroupHooks();

            $this->entityManager->remove($blockGroup);
            $this->entityManager->flush();

            foreach ($blockGroupHooks as $blockGroupHook) {
                $this->blockGroupHookRepository->cleanPositions($blockGroupHook->getHookId());
            }
        } catch (\Exception $exception) {
            throw new CannotDeleteBlockGroupException('An unexpected error occurred when deleting block group', 0, $exception);
        }
    }
}
