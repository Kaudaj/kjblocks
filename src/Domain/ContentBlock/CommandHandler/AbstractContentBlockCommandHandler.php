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

namespace Kaudaj\Module\ContentBlocks\Domain\ContentBlock\CommandHandler;

use Doctrine\ORM\EntityManager;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Exception\ContentBlockNotFoundException;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\ValueObject\Content;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\ValueObject\Name;
use Kaudaj\Module\ContentBlocks\Entity\ContentBlock;
use Kaudaj\Module\ContentBlocks\Entity\ContentBlockHook;
use Kaudaj\Module\ContentBlocks\Entity\ContentBlockLang;
use Kaudaj\Module\ContentBlocks\Repository\ContentBlockHookRepository;
use Kaudaj\Module\ContentBlocks\Repository\ContentBlockRepository;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShopBundle\Entity\Lang;
use PrestaShopBundle\Entity\Repository\LangRepository;
use PrestaShopDatabaseException;
use PrestaShopException;

/**
 * Class AbstractContentBlockCommandHandler.
 */
abstract class AbstractContentBlockCommandHandler
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ContentBlockRepository
     */
    protected $entityRepository;

    /**
     * @var LangRepository
     */
    protected $langRepository;

    /**
     * @var ContentBlockHookRepository
     */
    protected $contentBlockHookRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        /** @var ContentBlockRepository */
        $entityRepository = $this->entityManager->getRepository(ContentBlock::class);
        $this->entityRepository = $entityRepository;

        $langRepository = $this->entityManager->getRepository(Lang::class);
        $this->langRepository = $langRepository;

        $contentBlockHookRepository = $this->entityManager->getRepository(ContentBlockHook::class);
        $this->contentBlockHookRepository = $contentBlockHookRepository;
    }

    /**
     * Gets content block entity.
     *
     * @throws ContentBlockNotFoundException
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    protected function getContentBlockEntity(int $id): ContentBlock
    {
        /** @var ContentBlock|null */
        $contentBlock = $this->entityRepository->find($id);

        if (!$contentBlock) {
            throw new ContentBlockNotFoundException();
        }

        return $contentBlock;
    }

    /**
     * @param array<int, Name> $localizedNames
     * @param array<int, Content> $localizedContents
     *
     * @return ContentBlockLang[]
     */
    protected function createContentBlockLangs(array $localizedNames, array $localizedContents): array
    {
        /** @var Lang[] */
        $langs = $this->langRepository->findAll();

        $defaultLangId = (new Configuration())->getInt('PS_LANG_DEFAULT');
        $defaultName = $localizedNames[$defaultLangId]->getValue();
        $defaultContent = $localizedContents[$defaultLangId]->getValue();

        $contentBlockLangs = [];
        foreach ($langs as $lang) {
            $langId = $lang->getId();
            $name = key_exists($langId, $localizedNames) ? $localizedNames[$langId]->getValue() : $defaultName;
            $content = key_exists($langId, $localizedContents) ? $localizedContents[$langId]->getValue() : $defaultContent;

            $contentBlockLang = new ContentBlockLang();

            $contentBlockLang->setLang($lang);
            $contentBlockLang->setName($name);
            $contentBlockLang->setContent($content);

            $contentBlockLangs[] = $contentBlockLang;
        }

        return $contentBlockLangs;
    }
}
