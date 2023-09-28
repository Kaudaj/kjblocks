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

namespace Kaudaj\Module\Blocks;

use Kaudaj\Module\Blocks\Entity\Block;
use Kaudaj\Module\Blocks\Entity\BlockLang;
use Kaudaj\Module\Blocks\Entity\BlockShop;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\Shop\Context as ShopContext;

class BlockContext
{
    /**
     * @var int
     */
    private $contextLangId;

    /**
     * @var ShopContext
     */
    private $shopContext;

    public function __construct(int $contextLangId, ShopContext $shopContext)
    {
        $this->contextLangId = $contextLangId;
        $this->shopContext = $shopContext;
    }

    public function getBlockLang(Block $block): ?BlockLang
    {
        $blockLang = $block->getBlockLang($this->contextLangId);

        if ($blockLang !== null) {
            return $blockLang;
        }

        $defaultLangId = (new Configuration())->getInt('PS_LANG_DEFAULT');

        return $block->getBlockLang($defaultLangId);
    }

    public function getBlockShop(Block $block): ?BlockShop
    {
        $shopConstraint = $this->shopContext->getShopConstraint();
        $shopId = $shopConstraint->getShopId() !== null ? $shopConstraint->getShopId()->getValue() : null;
        $shopGroupId = $shopConstraint->getShopGroupId() !== null ? $shopConstraint->getShopGroupId()->getValue() : null;

        $blockShop = $block->getBlockShop($shopId, $shopGroupId);

        if ($blockShop !== null) {
            return $blockShop;
        }

        if ($shopId !== null && $shopGroupId === null) {
            $shopGroupId = (int) \Shop::getGroupFromShop($shopId) ?: $shopGroupId;
        }

        $blockShop = $block->getBlockShop(null, $shopGroupId);

        if ($blockShop !== null) {
            return $blockShop;
        }

        return $block->getBlockShop(null, null);
    }

    /**
     * @return array<string, mixed>
     */
    public function getBlockOptions(Block $block): array
    {
        $blockOptions = [];

        foreach ($this->getBlockShops($block) as $blockShop) {
            $blockShopOptions = json_decode($blockShop->getOptions() ?: '', true);
            $blockShopOptions = (is_array($blockShopOptions) ? $blockShopOptions : []);

            $blockOptions += $blockShopOptions;
        }

        return $blockOptions;
    }

    /**
     * @return BlockShop[]
     */
    private function getBlockShops(Block $block): array
    {
        $shopConstraint = $this->shopContext->getShopConstraint();
        $contextShopId = $shopConstraint->getShopId() !== null ? $shopConstraint->getShopId()->getValue() : null;
        $contextShopGroupId = $shopConstraint->getShopGroupId() !== null ? $shopConstraint->getShopGroupId()->getValue() : null;

        $shopContexts = [
            [$contextShopId, $contextShopGroupId],
        ];

        if ($contextShopId !== null && $contextShopGroupId === null) {
            $shopGroupId = (int) \Shop::getGroupFromShop($contextShopId) ?: $contextShopGroupId;

            $shopContexts[] = [$contextShopId, $shopGroupId];
        }

        $shopContexts[] = [null, null];

        $blockShops = [];

        foreach ($shopContexts as $shopContext) {
            $blockShop = $block->getBlockShop($shopContext[0], $shopContext[1]);

            if ($blockShop === null) {
                continue;
            }

            $blockShops[] = $blockShop;
        }

        return $blockShops;
    }
}
