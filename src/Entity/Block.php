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
use Kaudaj\Module\Blocks\Repository\BlockRepository;

/**
 * @ORM\Table(name=BlockRepository::TABLE_NAME_WITH_PREFIX)
 * @ORM\Entity(repositoryClass=BlockRepository::class)
 */
class Block
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id_block", type="integer")
     */
    private $id;

    /**
     * @var Collection<int, BlockLang>
     *
     * @ORM\OneToMany(targetEntity=BlockLang::class, cascade={"persist", "remove"}, mappedBy="block")
     */
    private $blockLangs;

    /**
     * @var Collection<int, BlockShop>
     *
     * @ORM\OneToMany(targetEntity=BlockShop::class, mappedBy="block", cascade={"persist", "remove"})
     */
    protected $blockShops;

    /**
     * @var Collection<int, BlockGroupBlock>
     *
     * @ORM\OneToMany(targetEntity=BlockGroupBlock::class, cascade={"persist", "remove"}, mappedBy="block")
     */
    private $blockGroups;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @var string|null
     *
     * @ORM\Column(type="json", nullable=true)
     */
    private $options;

    public function __construct()
    {
        $this->blockLangs = new ArrayCollection();
        $this->blockGroups = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, BlockGroupBlock>
     */
    public function getBlockGroups(): Collection
    {
        return $this->blockGroups;
    }

    public function getBlockGroup(int $groupId): ?BlockGroupBlock
    {
        foreach ($this->blockGroups as $blockGroup) {
            if ($groupId === $blockGroup->getBlockGroup()->getId()) {
                return $blockGroup;
            }
        }

        return null;
    }

    public function addBlockGroup(BlockGroupBlock $blockGroup): self
    {
        if (!$this->blockGroups->contains($blockGroup)) {
            $this->blockGroups[] = $blockGroup;

            $blockGroup->setBlock($this);
        }

        return $this;
    }

    public function removeBlockGroup(BlockGroupBlock $blockGroup): self
    {
        $this->blockGroups->removeElement($blockGroup);

        return $this;
    }

    /**
     * @return Collection<int, BlockLang>
     */
    public function getBlockLangs(): Collection
    {
        return $this->blockLangs;
    }

    public function getBlockLang(int $langId): ?BlockLang
    {
        foreach ($this->blockLangs as $blockLang) {
            if ($langId == $blockLang->getLang()->getId()) {
                return $blockLang;
            }
        }

        return null;
    }

    public function addBlockLang(BlockLang $blockLang): self
    {
        if (!$this->blockLangs->contains($blockLang)) {
            $this->blockLangs[] = $blockLang;
            $blockLang->setBlock($this);
        }

        return $this;
    }

    public function removeBlockLang(BlockLang $blockLang): self
    {
        $this->blockLangs->removeElement($blockLang);

        return $this;
    }

    /**
     * @return Collection<int, BlockShop>
     */
    public function getBlockShops(): Collection
    {
        return $this->blockShops;
    }

    public function getBlockShop(?int $shopId, ?int $shopGroupId): ?BlockShop
    {
        foreach ($this->blockShops as $blockShop) {
            $currentShopId = $blockShop->getShop() ? $blockShop->getShop()->getId() : null;
            $currentShopGroupId = $blockShop->getShopGroup() ? $blockShop->getShopGroup()->getId() : null;

            if ($shopId === $currentShopId && $shopGroupId === $currentShopGroupId) {
                return $blockShop;
            }
        }

        return null;
    }

    public function addBlockShop(BlockShop $blockShop): self
    {
        if (!$this->blockShops->contains($blockShop)) {
            $this->blockShops[] = $blockShop;
            $blockShop->setBlock($this);
        }

        return $this;
    }

    public function removeBlockShop(BlockShop $blockShop): self
    {
        $this->blockShops->removeElement($blockShop);

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getOptions(): ?string
    {
        return $this->options;
    }

    public function setOptions(?string $options): self
    {
        $this->options = $options;

        return $this;
    }
}
