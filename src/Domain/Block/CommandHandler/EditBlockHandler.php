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

use Kaudaj\Module\Blocks\Domain\Block\Command\EditBlockCommand;
use Kaudaj\Module\Blocks\Domain\Block\Exception\BlockException;
use Kaudaj\Module\Blocks\Domain\Block\Exception\CannotUpdateBlockException;
use Kaudaj\Module\Blocks\Entity\Block;
use Kaudaj\Module\Blocks\Entity\BlockHook;
use PrestaShop\PrestaShop\Core\Domain\Hook\ValueObject\HookId;
use PrestaShopException;

/**
 * Class EditBlockHandler is responsible for editing block data.
 *
 * @internal
 */
final class EditBlockHandler extends AbstractBlockCommandHandler
{
    /**
     * @throws BlockException
     */
    public function handle(EditBlockCommand $command): void
    {
        try {
            $block = $this->getBlockEntity(
                $command->getBlockId()->getValue()
            );

            if ($command->getType() !== null) {
                $block->setType($command->getType());
            }

            if ($command->getOptions() !== null) {
                $block->setOptions($command->getOptions()->getValue());
            }

            $hooksIds = $command->getHooksIds();
            if ($hooksIds) {
                $hooksIds = array_map(function (HookId $hookId): int {
                    return $hookId->getValue();
                }, $hooksIds);

                $this->updateHooks($block, $hooksIds);
            }

            $localizedNames = $command->getLocalizedNames();

            if (null !== $localizedNames) {
                foreach ($block->getBlockLangs() as $blockLangs) {
                    $block->removeBlockLang($blockLangs);

                    $this->entityManager->remove($blockLangs);
                }

                $this->entityManager->flush();

                $blockLangs = $this->createBlockLangs($localizedNames);
                foreach ($blockLangs as $blockLang) {
                    $block->addBlockLang($blockLang);

                    $this->entityManager->persist($blockLang);
                }
            }

            $this->entityManager->persist($block);
            $this->entityManager->flush();
        } catch (PrestaShopException $exception) {
            throw new CannotUpdateBlockException('An unexpected error occurred when editing block', 0, $exception);
        }
    }

    /**
     * @param int[] $hooksIds
     */
    protected function updateHooks(Block $block, array $hooksIds): void
    {
        foreach ($hooksIds as $hookId) {
            if ($block->getBlockHook($hookId)) {
                continue;
            }

            $blockHook = new BlockHook();

            $blockHook->setHookId($hookId);
            $blockHook->setPosition($this->blockHookRepository->findMaxPosition($hookId) + 1);

            $block->addBlockHook($blockHook);
        }

        foreach ($block->getBlockHooks() as $blockHook) {
            $hookId = $blockHook->getHookId();
            if (in_array($hookId, $hooksIds)) {
                continue;
            }

            $block->removeBlockHook($blockHook);
            $this->entityManager->remove($blockHook);

            $this->blockHookRepository->cleanPositions($hookId);
        }
    }
}
