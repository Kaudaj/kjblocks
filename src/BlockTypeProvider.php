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

namespace Kaudaj\Module\Blocks;

use Kaudaj\Module\Blocks\Block as BlockType;
use Kaudaj\Module\Blocks\Entity\Block;

/**
 * @extends AbstractBlockTypeProvider<BlockInterface>
 */
class BlockTypeProvider extends AbstractBlockTypeProvider
{
    /**
     * @var BlockContext
     */
    protected $blockContext;

    public function __construct(int $contextLangId, string $hookName, BlockContext $blockContext)
    {
        parent::__construct($contextLangId, $hookName);

        $this->blockContext = $blockContext;
    }

    public function getBlockTypeFromEntity(Block $block): BlockInterface
    {
        $id = $block->getId();

        if (!key_exists($id, $this->blocks)) {
            $this->blocks[$id] = $this->getBlockType($block->getType(), $this->blockContext->getBlockOptions($block) + [
                BlockType::OPTION_ID => $id,
            ]);
        }

        return $this->blocks[$id];
    }
}
