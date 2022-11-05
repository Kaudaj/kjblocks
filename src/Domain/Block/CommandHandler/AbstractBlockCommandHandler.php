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
use Kaudaj\Module\Blocks\Domain\Block\ValueObject\Content;
use Kaudaj\Module\Blocks\Domain\Block\ValueObject\Name;
use Kaudaj\Module\Blocks\Entity\Block;
use Kaudaj\Module\Blocks\Entity\BlockHook;
use Kaudaj\Module\Blocks\Entity\BlockLang;
use Kaudaj\Module\Blocks\Repository\BlockHookRepository;
use Kaudaj\Module\Blocks\Repository\BlockRepository;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShopBundle\Entity\Lang;
use PrestaShopBundle\Entity\Repository\LangRepository;
use PrestaShopDatabaseException;
use PrestaShopException;

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
     * @var BlockHookRepository
     */
    protected $blockHookRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        /** @var BlockRepository */
        $entityRepository = $this->entityManager->getRepository(Block::class);
        $this->entityRepository = $entityRepository;

        $langRepository = $this->entityManager->getRepository(Lang::class);
        $this->langRepository = $langRepository;

        $blockHookRepository = $this->entityManager->getRepository(BlockHook::class);
        $this->blockHookRepository = $blockHookRepository;
    }

    /**
     * Gets block entity.
     *
     * @throws BlockNotFoundException
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
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
     * @param array<int, Content> $localizedContents
     *
     * @return BlockLang[]
     */
    protected function createBlockLangs(array $localizedNames, array $localizedContents): array
    {
        /** @var Lang[] */
        $langs = $this->langRepository->findAll();

        $defaultLangId = (new Configuration())->getInt('PS_LANG_DEFAULT');
        $defaultName = $localizedNames[$defaultLangId]->getValue();
        $defaultContent = $localizedContents[$defaultLangId]->getValue();

        $blockLangs = [];
        foreach ($langs as $lang) {
            $langId = $lang->getId();
            $name = key_exists($langId, $localizedNames) ? $localizedNames[$langId]->getValue() : $defaultName;
            $content = key_exists($langId, $localizedContents) ? $localizedContents[$langId]->getValue() : $defaultContent;

            $blockLang = new BlockLang();

            $blockLang->setLang($lang);
            $blockLang->setName($name);
            $blockLang->setContent($content);

            $blockLangs[] = $blockLang;
        }

        return $blockLangs;
    }
}
