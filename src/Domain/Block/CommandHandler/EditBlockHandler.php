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
use Kaudaj\Module\Blocks\BlockContext;
use Kaudaj\Module\Blocks\Domain\Block\Command\EditBlockCommand;
use Kaudaj\Module\Blocks\Domain\Block\Exception\BlockException;
use Kaudaj\Module\Blocks\Domain\Block\Exception\CannotUpdateBlockException;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Exception\BlockGroupNotFoundException;
use Kaudaj\Module\Blocks\Domain\BlockGroup\ValueObject\BlockGroupId;
use Kaudaj\Module\Blocks\Entity\Block;
use Kaudaj\Module\Blocks\Entity\BlockGroup;
use Kaudaj\Module\Blocks\Entity\BlockGroupBlock;
use Kaudaj\Module\Blocks\Repository\BlockGroupRepository;

/**
 * Class EditBlockHandler is responsible for editing block data.
 *
 * @internal
 */
final class EditBlockHandler extends AbstractBlockCommandHandler
{
    /**
     * @var BlockContext
     */
    private $blockContext;

    public function __construct(EntityManager $entityManager, BlockContext $blockContext)
    {
        parent::__construct($entityManager);

        $this->blockContext = $blockContext;
    }

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

            $blockGroupsIds = $command->getBlockGroupsIds();
            if ($blockGroupsIds) {
                $blockGroupsIds = array_map(function (BlockGroupId $blockGroupId): int {
                    return $blockGroupId->getValue();
                }, $blockGroupsIds);

                $this->updateBlockGroups($block, $blockGroupsIds);
            }

            $localizedNames = $command->getLocalizedNames();

            if (null !== $localizedNames) {
                foreach ($block->getBlockLangs() as $blockLang) {
                    $block->removeBlockLang($blockLang);

                    $this->entityManager->remove($blockLang);
                }

                $this->entityManager->flush();

                $blockLangs = $this->createBlockLangs($localizedNames);
                foreach ($blockLangs as $blockLang) {
                    $block->addBlockLang($blockLang);

                    $this->entityManager->persist($blockLang);
                }
            }

            $shopConstraint = $command->getShopConstraint();
            $shopId = $shopConstraint->getShopId() !== null ? $shopConstraint->getShopId()->getValue() : null;
            $shopGroupId = $shopConstraint->getShopGroupId() !== null ? $shopConstraint->getShopGroupId()->getValue() : null;

            $contextBlockShop = $this->blockContext->getBlockShop($block);

            $blockShop = $block->getBlockShop($shopId, $shopGroupId);
            if ($blockShop) {
                $block->removeBlockShop($blockShop);
                $this->entityManager->remove($blockShop);
            }

            $this->entityManager->flush();

            $blockShop = $this->createBlockShop($shopId, $shopGroupId);
            $blockShop->setOptions($command->getOptions() !== null ? $command->getOptions()->getValue() : null);

            if ($contextBlockShop !== null) {
                $blockShop->setActive($contextBlockShop->isActive());
            }

            $block->addBlockShop($blockShop);

            $this->entityManager->persist($block);
            $this->entityManager->flush();
        } catch (\PrestaShopException $exception) {
            throw new CannotUpdateBlockException('An unexpected error occurred when editing block', 0, $exception);
        }
    }

    /**
     * @param int[] $blockGroupsIds
     */
    protected function updateBlockGroups(Block $block, array $blockGroupsIds): void
    {
        /** @var BlockGroupRepository */
        $blockGroupRepository = $this->entityManager->getRepository(BlockGroup::class);

        foreach ($blockGroupsIds as $blockGroupId) {
            $blockGroup = $blockGroupRepository->find($blockGroupId);
            if ($blockGroup === null) {
                throw new BlockGroupNotFoundException();
            }

            $blockGroupBlock = new BlockGroupBlock();
            $existingBlockGroup = $block->getBlockGroup($blockGroupId);

            if ($existingBlockGroup) {
                $blockGroupBlock->setPosition($existingBlockGroup->getPosition());

                $block->removeBlockGroup($existingBlockGroup);
                $this->entityManager->remove($existingBlockGroup);
                $this->entityManager->flush();
            } else {
                $blockGroupBlock->setPosition($this->blockGroupBlockRepository->findMaxPosition($blockGroupId) + 1);
            }

            $blockGroupBlock->setBlockGroup($blockGroup);

            $block->addBlockGroup($blockGroupBlock);
        }

        foreach ($block->getBlockGroups() as $blockGroupBlock) {
            $blockGroupId = $blockGroupBlock->getBlockGroup()->getId();
            if (in_array($blockGroupId, $blockGroupsIds)) {
                continue;
            }

            $block->removeBlockGroup($blockGroupBlock);
            $this->entityManager->remove($blockGroupBlock);

            $this->blockGroupBlockRepository->cleanPositions($blockGroupId);
        }
    }
}
