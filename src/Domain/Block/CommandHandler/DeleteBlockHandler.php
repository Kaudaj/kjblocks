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

use Doctrine\ORM\EntityManager;
use Kaudaj\Module\Blocks\Domain\Block\Command\DeleteBlockCommand;
use Kaudaj\Module\Blocks\Domain\Block\Exception\BlockException;
use Kaudaj\Module\Blocks\Domain\Block\Exception\CannotDeleteBlockException;
use Kaudaj\Module\Blocks\File\BlockFileManager;

/**
 * Class DeleteBlockHandler is responsible for deleting block data.
 *
 * @internal
 */
final class DeleteBlockHandler extends AbstractBlockCommandHandler
{
    /**
     * @var BlockFileManager
     */
    private $blockFileManager;

    public function __construct(EntityManager $entityManager, BlockFileManager $blockFileManager)
    {
        parent::__construct($entityManager);

        $this->blockFileManager = $blockFileManager;
    }

    /**
     * @throws BlockException
     */
    public function handle(DeleteBlockCommand $command): void
    {
        $blockId = $command->getBlockId()->getValue();

        $block = $this->getBlockEntity($blockId);

        try {
            $blockGroupBlocks = $block->getBlockGroups();

            $this->entityManager->remove($block);
            $this->entityManager->flush();

            foreach ($blockGroupBlocks as $blockGroupBlock) {
                $this->blockGroupBlockRepository->cleanPositions($blockGroupBlock->getBlockGroup()->getId());
            }

            $this->blockFileManager->delete($blockId);
        } catch (\Exception $exception) {
            throw new CannotDeleteBlockException('An unexpected error occurred when deleting block', 0, $exception);
        }
    }
}
