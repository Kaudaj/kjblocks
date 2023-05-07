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
use Kaudaj\Module\Blocks\Domain\Block\Exception\BlockNotFoundException;
use Kaudaj\Module\Blocks\Domain\Block\ValueObject\Name;
use Kaudaj\Module\Blocks\Entity\Block;
use Kaudaj\Module\Blocks\Entity\BlockGroupBlock;
use Kaudaj\Module\Blocks\Entity\BlockLang;
use Kaudaj\Module\Blocks\Entity\BlockShop;
use Kaudaj\Module\Blocks\Repository\BlockGroupBlockRepository;
use Kaudaj\Module\Blocks\Repository\BlockRepository;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShopBundle\Entity\Lang;
use PrestaShopBundle\Entity\Repository\LangRepository;
use PrestaShopBundle\Entity\Repository\ShopGroupRepository;
use PrestaShopBundle\Entity\Repository\ShopRepository;
use PrestaShopBundle\Entity\Shop;
use PrestaShopBundle\Entity\ShopGroup;

/**
 * Class AbstractBlockCommandHandler.
 */
abstract class AbstractBlockCommandHandler
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var BlockRepository
     */
    protected $entityRepository;

    /**
     * @var LangRepository
     */
    protected $langRepository;

    /**
     * @var ShopRepository
     */
    protected $shopRepository;

    /**
     * @var ShopGroupRepository
     */
    protected $shopGroupRepository;

    /**
     * @var BlockGroupBlockRepository
     */
    protected $blockGroupBlockRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        /** @var BlockRepository */
        $entityRepository = $this->entityManager->getRepository(Block::class);
        $this->entityRepository = $entityRepository;

        $langRepository = $this->entityManager->getRepository(Lang::class);
        $this->langRepository = $langRepository;

        $shopRepository = $this->entityManager->getRepository(Shop::class);
        $this->shopRepository = $shopRepository;

        $shopGroupRepository = $this->entityManager->getRepository(ShopGroup::class);
        $this->shopGroupRepository = $shopGroupRepository;

        /** @var BlockGroupBlockRepository */
        $blockGroupBlockRepository = $this->entityManager->getRepository(BlockGroupBlock::class);
        $this->blockGroupBlockRepository = $blockGroupBlockRepository;
    }

    /**
     * Gets block entity.
     *
     * @throws BlockNotFoundException
     * @throws \PrestaShopException
     * @throws \PrestaShopDatabaseException
     */
    protected function getBlockEntity(int $id): Block
    {
        /** @var Block|null */
        $block = $this->entityRepository->find($id);

        if (!$block) {
            throw new BlockNotFoundException();
        }

        return $block;
    }

    /**
     * @param array<int, Name> $localizedNames
     *
     * @return BlockLang[]
     */
    protected function createBlockLangs(array $localizedNames): array
    {
        /** @var Lang[] */
        $langs = $this->langRepository->findAll();

        $defaultLangId = (new Configuration())->getInt('PS_LANG_DEFAULT');
        $defaultName = $localizedNames[$defaultLangId]->getValue();

        $blockLangs = [];
        foreach ($langs as $lang) {
            $langId = $lang->getId();
            $name = key_exists($langId, $localizedNames) ? $localizedNames[$langId]->getValue() : $defaultName;

            $blockLang = new BlockLang();

            $blockLang->setLang($lang);
            $blockLang->setName($name);

            $blockLangs[] = $blockLang;
        }

        return $blockLangs;
    }

    protected function createBlockShop(?int $shopId, ?int $shopGroupId): BlockShop
    {
        $blockShop = new BlockShop();

        $shop = $shopGroup = null;

        if ($shopId !== null) {
            /** @var Shop|null */
            $shop = $this->shopRepository->find($shopId);
        }

        if ($shopGroupId !== null) {
            /** @var ShopGroup|null */
            $shopGroup = $this->shopGroupRepository->find($shopGroupId);
        }

        $blockShop->setShop($shop);
        $blockShop->setShopGroup($shopGroup);

        return $blockShop;
    }
}
