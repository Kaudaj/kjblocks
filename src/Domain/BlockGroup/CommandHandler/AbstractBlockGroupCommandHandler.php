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

namespace Kaudaj\Module\Blocks\Domain\BlockGroup\CommandHandler;

use Doctrine\ORM\EntityManager;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Exception\BlockGroupNotFoundException;
use Kaudaj\Module\Blocks\Domain\BlockGroup\ValueObject\Name;
use Kaudaj\Module\Blocks\Entity\BlockGroup;
use Kaudaj\Module\Blocks\Entity\BlockGroupHook;
use Kaudaj\Module\Blocks\Entity\BlockGroupLang;
use Kaudaj\Module\Blocks\Repository\BlockGroupHookRepository;
use Kaudaj\Module\Blocks\Repository\BlockGroupRepository;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShopBundle\Entity\Lang;
use PrestaShopBundle\Entity\Repository\LangRepository;

abstract class AbstractBlockGroupCommandHandler
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var BlockGroupRepository
     */
    protected $entityRepository;

    /**
     * @var LangRepository
     */
    protected $langRepository;

    /**
     * @var BlockGroupHookRepository
     */
    protected $blockGroupHookRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        /** @var BlockGroupRepository */
        $entityRepository = $this->entityManager->getRepository(BlockGroup::class);
        $this->entityRepository = $entityRepository;

        $langRepository = $this->entityManager->getRepository(Lang::class);
        $this->langRepository = $langRepository;

        $blockGroupHookRepository = $this->entityManager->getRepository(BlockGroupHook::class);
        $this->blockGroupHookRepository = $blockGroupHookRepository;
    }

    /**
     * @throws BlockGroupNotFoundException
     * @throws \PrestaShopException
     * @throws \PrestaShopDatabaseException
     */
    protected function getBlockGroupEntity(int $id): BlockGroup
    {
        /** @var BlockGroup|null */
        $blockGroup = $this->entityRepository->find($id);

        if (!$blockGroup) {
            throw new BlockGroupNotFoundException();
        }

        return $blockGroup;
    }

    /**
     * @param array<int, Name> $localizedNames
     *
     * @return BlockGroupLang[]
     */
    protected function createBlockGroupLangs(array $localizedNames): array
    {
        /** @var Lang[] */
        $langs = $this->langRepository->findAll();

        $defaultLangId = (new Configuration())->getInt('PS_LANG_DEFAULT');
        $defaultName = $localizedNames[$defaultLangId]->getValue();

        $blockGroupLangs = [];
        foreach ($langs as $lang) {
            $langId = $lang->getId();
            $name = key_exists($langId, $localizedNames) ? $localizedNames[$langId]->getValue() : $defaultName;

            $blockGroupLang = new BlockGroupLang();

            $blockGroupLang->setLang($lang);
            $blockGroupLang->setName($name);

            $blockGroupLangs[] = $blockGroupLang;
        }

        return $blockGroupLangs;
    }
}
