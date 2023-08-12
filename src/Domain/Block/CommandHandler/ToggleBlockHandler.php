<?php
/**
 * Copyright since 2011 Prestarocket
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contact@prestarocket.com so we can send you a copy immediately.
 *
 * @author    Prestarocket <contact@prestarocket.com>
 * @copyright Since 2011 Prestarocket
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

declare(strict_types=1);

namespace Kaudaj\Module\Blocks\Domain\Block\CommandHandler;

use Doctrine\ORM\EntityManager;
use Kaudaj\Module\Blocks\BlockContext;
use Kaudaj\Module\Blocks\Domain\Block\Command\ToggleBlockCommand;
use Kaudaj\Module\Blocks\Domain\Block\Exception\BlockException;
use Kaudaj\Module\Blocks\Domain\Block\Exception\CannotToggleBlockException;

/**
 * Class ToggleBlockHandler is responsible for toggling block status.
 *
 * @internal
 */
final class ToggleBlockHandler extends AbstractBlockCommandHandler
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
    public function handle(ToggleBlockCommand $command): void
    {
        $block = $this->getBlockEntity(
            $command->getBlockId()->getValue()
        );

        $shopConstraint = $command->getShopConstraint();
        $shopId = $shopConstraint->getShopId() !== null ? $shopConstraint->getShopId()->getValue() : null;
        $shopGroupId = $shopConstraint->getShopGroupId() !== null ? $shopConstraint->getShopGroupId()->getValue() : null;

        $blockShop = $block->getBlockShop($shopId, $shopGroupId);

        if ($blockShop) {
            $blockShop->setActive(!$blockShop->isActive());
        } else {
            $contextBlockShop = $this->blockContext->getBlockShop($block);

            $blockShop = $this->createBlockShop($shopId, $shopGroupId);
            $block->addBlockShop($blockShop);

            $blockShop->setActive($contextBlockShop ? !$contextBlockShop->isActive() : !$blockShop->isActive());
            $blockShop->setOptions($contextBlockShop ? $contextBlockShop->getOptions() : null);
        }

        try {
            $this->entityManager->persist($block);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            throw new CannotToggleBlockException('An unexpected error occurred when toggling block status', 0, $exception);
        }
    }
}
