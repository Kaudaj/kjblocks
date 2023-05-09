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
use Kaudaj\Module\Blocks\Repository\BlockRepository;
use PrestaShopBundle\Entity\Shop;
use PrestaShopBundle\Entity\ShopGroup;

/**
 * @ORM\Entity()
 * @ORM\Table(name=BlockRepository::SHOP_TABLE_NAME_WITH_PREFIX)
 */
class BlockShop
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", name="id_block_shop")
     */
    private $id;

    /**
     * @var Block
     *
     * @ORM\ManyToOne(targetEntity=Block::class, inversedBy="blockShops")
     * @ORM\JoinColumn(name="id_block", referencedColumnName="id_block", onDelete="CASCADE")
     */
    private $block;

    /**
     * @var Shop|null
     *
     * @ORM\ManyToOne(targetEntity=Shop::class)
     * @ORM\JoinColumn(name="id_shop", referencedColumnName="id_shop", onDelete="CASCADE")
     */
    private $shop;

    /**
     * @var ShopGroup|null
     *
     * @ORM\ManyToOne(targetEntity=ShopGroup::class)
     * @ORM\JoinColumn(name="id_shop_group", referencedColumnName="id_shop_group", onDelete="CASCADE")
     */
    private $shopGroup;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $active = true;

    public function getId(): int
    {
        return $this->id;
    }

    public function getBlock(): Block
    {
        return $this->block;
    }

    public function setBlock(Block $block): self
    {
        $this->block = $block;

        return $this;
    }

    public function getShop(): ?Shop
    {
        return $this->shop;
    }

    public function setShop(?Shop $shop): self
    {
        $this->shop = $shop;

        return $this;
    }

    public function getShopGroup(): ?ShopGroup
    {
        return $this->shopGroup;
    }

    public function setShopGroup(?ShopGroup $shopGroup): self
    {
        $this->shopGroup = $shopGroup;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }
}
