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

use Kaudaj\Module\Blocks\Domain\Block\Command\AddBlockCommand;
use Kaudaj\Module\Blocks\Domain\Block\Exception\BlockException;
use Kaudaj\Module\Blocks\Domain\Block\Exception\CannotAddBlockException;
use Kaudaj\Module\Blocks\Domain\Block\ValueObject\BlockId;
use Kaudaj\Module\Blocks\Entity\Block;
use Kaudaj\Module\Blocks\Entity\BlockHook;

/**
 * Class AddBlockHandler is used for adding block data.
 */
final class AddBlockHandler extends AbstractBlockCommandHandler
{
    /**
     * @throws CannotAddBlockException
     * @throws BlockException
     */
    public function handle(AddBlockCommand $command): BlockId
    {
        try {
            $block = new Block();

            $block->setType($command->getType());
            $block->setOptions($command->getOptions() !== null ? $command->getOptions()->getValue() : null);

            foreach ($command->getHooksIds() as $hookId) {
                $blockHook = new BlockHook();

                $hookId = $hookId->getValue();

                $blockHook->setHookId($hookId);
                $blockHook->setPosition($this->blockHookRepository->findMaxPosition($hookId) + 1);

                $block->addBlockHook($blockHook);
            }

            $localizedNames = $command->getLocalizedNames();

            $configuratorBlockLangs = $this->createBlockLangs($localizedNames);
            foreach ($configuratorBlockLangs as $configuratorBlockLang) {
                $block->addBlockLang($configuratorBlockLang);
            }

            $this->entityManager->persist($block);
            $this->entityManager->flush();
        } catch (\PrestaShopException $exception) {
            throw new BlockException('An unexpected error occurred when adding block', 0, $exception);
        }

        return new BlockId((int) $block->getId());
    }
}
