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

namespace Kaudaj\Module\Blocks\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kaudaj\Module\Blocks\Repository\BlockGroupBlockRepository;

/**
 * @ORM\Table(name=BlockGroupBlockRepository::TABLE_NAME_WITH_PREFIX)
 * @ORM\Entity(repositoryClass=BlockGroupBlockRepository::class)
 */
class BlockGroupBlock
{
    /**
     * @var Block
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=Block::class, inversedBy="blocks")
     * @ORM\JoinColumn(name="id_block", referencedColumnName="id_block", nullable=false, onDelete="CASCADE")
     */
    private $block;

    /**
     * @var BlockGroup
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=BlockGroup::class, inversedBy="blockGroups")
     * @ORM\JoinColumn(name="id_block_group", referencedColumnName="id_block_group", nullable=false, onDelete="CASCADE")
     */
    private $blockGroup;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"unsigned": true})
     */
    private $position;

    public function getBlock(): Block
    {
        return $this->block;
    }

    public function setBlock(Block $block): self
    {
        $this->block = $block;

        return $this;
    }

    public function getBlockGroup(): BlockGroup
    {
        return $this->blockGroup;
    }

    public function setBlockGroup(BlockGroup $blockGroup): self
    {
        $this->blockGroup = $blockGroup;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }
}
