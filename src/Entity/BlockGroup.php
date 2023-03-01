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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kaudaj\Module\Blocks\Repository\BlockGroupRepository;

/**
 * @ORM\Table(name=BlockGroupRepository::TABLE_NAME_WITH_PREFIX)
 * @ORM\Entity(repositoryClass=BlockGroupRepository::class)
 */
class BlockGroup
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id_block_group", type="integer")
     */
    private $id;

    /**
     * @var Collection<int, BlockGroupBlock>
     *
     * @ORM\OneToMany(targetEntity=BlockGroupBlock::class, cascade={"persist", "remove"}, mappedBy="blockGroup")
     */
    private $blockGroupBlocks;

    /**
     * @var Collection<int, BlockGroupHook>
     *
     * @ORM\OneToMany(targetEntity=BlockGroupHook::class, cascade={"persist", "remove"}, mappedBy="blockGroup")
     */
    private $blockGroupHooks;

    /**
     * @var Collection<int, BlockGroupLang>
     *
     * @ORM\OneToMany(targetEntity=BlockGroupLang::class, cascade={"persist", "remove"}, mappedBy="blockGroup")
     */
    private $blockGroupLangs;

    public function __construct()
    {
        $this->blockGroupBlocks = new ArrayCollection();
        $this->blockGroupLangs = new ArrayCollection();
        $this->blockGroupHooks = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, BlockGroupBlock>
     */
    public function getBlockGroupBlocks(): Collection
    {
        return $this->blockGroupBlocks;
    }

    public function getBlockGroupBlock(int $blockId): ?BlockGroupBlock
    {
        foreach ($this->blockGroupBlocks as $blockGroupBlock) {
            if ($blockId == $blockGroupBlock->getBlock()->getId()) {
                return $blockGroupBlock;
            }
        }

        return null;
    }

    public function addBlockGroupBlock(BlockGroupBlock $blockGroupBlock): self
    {
        if (!$this->blockGroupBlocks->contains($blockGroupBlock)) {
            $this->blockGroupBlocks[] = $blockGroupBlock;
            $blockGroupBlock->setBlockGroup($this);
        }

        return $this;
    }

    public function removeBlockGroupBlock(BlockGroupBlock $blockGroupBlock): self
    {
        $this->blockGroupBlocks->removeElement($blockGroupBlock);

        return $this;
    }

    /**
     * @return Collection<int, BlockGroupHook>
     */
    public function getBlockGroupHooks(): Collection
    {
        return $this->blockGroupHooks;
    }

    public function getBlockGroupHook(int $hookId): ?BlockGroupHook
    {
        foreach ($this->blockGroupHooks as $blockGroupHook) {
            if ($hookId == $blockGroupHook->getHookId()) {
                return $blockGroupHook;
            }
        }

        return null;
    }

    public function addBlockGroupHook(BlockGroupHook $blockGroupHook): self
    {
        if (!$this->blockGroupHooks->contains($blockGroupHook)) {
            $this->blockGroupHooks[] = $blockGroupHook;
            $blockGroupHook->setBlockGroup($this);
        }

        return $this;
    }

    public function removeBlockGroupHook(BlockGroupHook $blockGroupLang): self
    {
        $this->blockGroupHooks->removeElement($blockGroupLang);

        return $this;
    }

    /**
     * @return Collection<int, BlockGroupLang>
     */
    public function getBlockGroupLangs(): Collection
    {
        return $this->blockGroupLangs;
    }

    public function getBlockGroupLang(int $langId): ?BlockGroupLang
    {
        foreach ($this->blockGroupLangs as $blockGroupLang) {
            if ($langId == $blockGroupLang->getLang()->getId()) {
                return $blockGroupLang;
            }
        }

        return null;
    }

    public function addBlockGroupLang(BlockGroupLang $blockGroupLang): self
    {
        if (!$this->blockGroupLangs->contains($blockGroupLang)) {
            $this->blockGroupLangs[] = $blockGroupLang;
            $blockGroupLang->setBlockGroup($this);
        }

        return $this;
    }

    public function removeBlockGroupLang(BlockGroupLang $blockGroupLang): self
    {
        $this->blockGroupLangs->removeElement($blockGroupLang);

        return $this;
    }
}
