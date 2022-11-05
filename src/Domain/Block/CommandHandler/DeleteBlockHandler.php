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

namespace Kaudaj\Module\Blocks\Domain\Block\CommandHandler;

use Exception;
use Kaudaj\Module\Blocks\Domain\Block\Command\DeleteBlockCommand;
use Kaudaj\Module\Blocks\Domain\Block\Exception\BlockException;
use Kaudaj\Module\Blocks\Domain\Block\Exception\CannotDeleteBlockException;

/**
 * Class DeleteBlockHandler is responsible for deleting block data.
 *
 * @internal
 */
final class DeleteBlockHandler extends AbstractBlockCommandHandler
{
    /**
     * @throws BlockException
     */
    public function handle(DeleteBlockCommand $command): void
    {
        $block = $this->getBlockEntity(
            $command->getBlockId()->getValue()
        );

        try {
            $blockHooks = $block->getBlockHooks();

            $this->entityManager->remove($block);
            $this->entityManager->flush();

            foreach ($blockHooks as $blockHook) {
                $this->blockHookRepository->cleanPositions($blockHook->getHookId());
            }
        } catch (Exception $exception) {
            throw new CannotDeleteBlockException('An unexpected error occurred when deleting block', 0, $exception);
        }
    }
}
