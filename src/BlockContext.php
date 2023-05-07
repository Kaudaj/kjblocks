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
            $shopGroupId = intval(\Shop::getGroupFromShop($shopId)) ?: $shopGroupId;
        }

        $blockShop = $block->getBlockShop(null, $shopGroupId);

        if ($blockShop !== null) {
            return $blockShop;
        }

        return $block->getBlockShop(null, null);
    }
}
