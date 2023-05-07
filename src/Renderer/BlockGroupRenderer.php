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

namespace Kaudaj\Module\Blocks\Renderer;

use Kaudaj\Module\Blocks\BlockContext;
use Kaudaj\Module\Blocks\BlockInterface;
use Kaudaj\Module\Blocks\BlockTypeProvider;
use Kaudaj\Module\Blocks\Entity\Block;
use Kaudaj\Module\Blocks\Entity\BlockGroup;
use Kaudaj\Module\Blocks\Entity\BlockGroupBlock;

class BlockGroupRenderer
{
    public const BACKGROUND_FILENAME = 'background';

    /**
     * @var BlockTypeProvider<BlockInterface>
     */
    private $blockTypeProvider;

    /**
     * @var BlockContext
     */
    private $blockContext;

    /**
     * @param BlockTypeProvider<BlockInterface> $blockTypeProvider
     */
    public function __construct(BlockTypeProvider $blockTypeProvider, BlockContext $blockContext)
    {
        $this->blockTypeProvider = $blockTypeProvider;
        $this->blockContext = $blockContext;
    }

    public function render(BlockGroup $blockGroup): string
    {
        $render = "<div class=\"block-group block-group-{$blockGroup->getId()}\">";

        $blockGroupBlocks = $blockGroup->getBlockGroupBlocks()->getValues();

        usort($blockGroupBlocks, function (BlockGroupBlock $blockGroupBlock1, BlockGroupBlock $blockGroupBlock2): int {
            if ($blockGroupBlock1->getPosition() == $blockGroupBlock2->getPosition()) {
                return 0;
            }

            return ($blockGroupBlock1->getPosition() < $blockGroupBlock2->getPosition()) ? -1 : 1;
        });

        $blocks = array_map(function (BlockGroupBlock $blockGroupBlock): Block {
            return $blockGroupBlock->getBlock();
        }, $blockGroupBlocks);

        foreach ($blocks as $block) {
            $blockShop = $this->blockContext->getBlockShop($block);
            if ($blockShop && !$blockShop->isActive()) {
                continue;
            }

            $render .= $this->blockTypeProvider->getBlockTypeFromEntity($block)->render();
        }

        $render .= '</div>';

        return $render;
    }
}
