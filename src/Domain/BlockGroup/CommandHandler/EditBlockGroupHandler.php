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

use Kaudaj\Module\Blocks\Domain\BlockGroup\Command\EditBlockGroupCommand;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Exception\BlockGroupException;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Exception\CannotUpdateBlockGroupException;
use Kaudaj\Module\Blocks\Entity\BlockGroup;
use Kaudaj\Module\Blocks\Entity\BlockGroupHook;
use PrestaShop\PrestaShop\Core\Domain\Hook\ValueObject\HookId;

/**
 * @internal
 */
final class EditBlockGroupHandler extends AbstractBlockGroupCommandHandler
{
    /**
     * @throws BlockGroupException
     */
    public function handle(EditBlockGroupCommand $command): void
    {
        try {
            $blockGroup = $this->getBlockGroupEntity(
                $command->getBlockGroupId()->getValue()
            );

            $hooksIds = $command->getHooksIds();
            if ($hooksIds) {
                $hooksIds = array_map(function (HookId $hookId): int {
                    return $hookId->getValue();
                }, $hooksIds);

                $this->updateHooks($blockGroup, $hooksIds);
            }

            $localizedNames = $command->getLocalizedNames();

            if (null !== $localizedNames) {
                foreach ($blockGroup->getBlockGroupLangs() as $blockGroupLangs) {
                    $blockGroup->removeBlockGroupLang($blockGroupLangs);

                    $this->entityManager->remove($blockGroupLangs);
                }

                $this->entityManager->flush();

                $blockGroupLangs = $this->createBlockGroupLangs($localizedNames);
                foreach ($blockGroupLangs as $blockGroupLang) {
                    $blockGroup->addBlockGroupLang($blockGroupLang);

                    $this->entityManager->persist($blockGroupLang);
                }
            }

            $this->entityManager->persist($blockGroup);
            $this->entityManager->flush();
        } catch (\PrestaShopException $exception) {
            throw new CannotUpdateBlockGroupException('An unexpected error occurred when editing blockGroup', 0, $exception);
        }
    }

    /**
     * @param int[] $hooksIds
     */
    protected function updateHooks(BlockGroup $blockGroup, array $hooksIds): void
    {
        foreach ($hooksIds as $hookId) {
            if ($blockGroup->getBlockGroupHook($hookId)) {
                continue;
            }

            $blockGroupHook = new BlockGroupHook();

            $blockGroupHook->setHookId($hookId);
            $blockGroupHook->setPosition($this->blockGroupHookRepository->findMaxPosition($hookId) + 1);

            $blockGroup->addBlockGroupHook($blockGroupHook);
        }

        foreach ($blockGroup->getBlockGroupHooks() as $blockGroupHook) {
            $hookId = $blockGroupHook->getHookId();
            if (in_array($hookId, $hooksIds)) {
                continue;
            }

            $blockGroup->removeBlockGroupHook($blockGroupHook);
            $this->entityManager->remove($blockGroupHook);

            $this->blockGroupHookRepository->cleanPositions($hookId);
        }
    }
}
