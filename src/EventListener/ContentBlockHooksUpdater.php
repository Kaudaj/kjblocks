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

namespace Kaudaj\Module\ContentBlocks\EventListener;

use Db;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Hook;
use Kaudaj\Module\ContentBlocks\Entity\ContentBlock;
use Kaudaj\Module\ContentBlocks\Repository\ContentBlockRepository;
use KJContentBlocks;
use Module;
use RuntimeException;

class ContentBlockHooksUpdater
{
    /**
     * @var ContentBlockRepository
     */
    private $entityRepository;

    /**
     * @var Module
     */
    private $module;

    /**
     * @var int
     */
    private $contextShopId;

    public function __construct(EntityManager $entityManager, int $contextShopId)
    {
        $this->entityRepository = $entityManager->getRepository(ContentBlock::class);

        $module = Module::getInstanceByName('kjcontentblocks');
        if (!($module instanceof KJContentBlocks)) {
            throw new RuntimeException("Can't update module hooks");
        }

        $this->module = $module;
        $this->contextShopId = $contextShopId;
    }

    public function postUpdate(ContentBlock $contentBlock, LifecycleEventArgs $event): void
    {
        $this->updateRegisteredHooks();
    }

    public function postPersist(ContentBlock $contentBlock, LifecycleEventArgs $event): void
    {
        $this->updateRegisteredHooks();
    }

    public function postRemove(ContentBlock $contentBlock, LifecycleEventArgs $event): void
    {
        $this->updateRegisteredHooks();
    }

    private function updateRegisteredHooks(): void
    {
        $getHookName = function (int $hookId): string {
            return Hook::getNameById($hookId);
        };

        $contentBlocks = $this->entityRepository->findAll();

        $contentBlocksHooks = [];
        foreach ($contentBlocks as $contentBlock) {
            foreach ($contentBlock->getContentBlockHooks() as $contentBlockHook) {
                $hookId = $contentBlockHook->getHookId();

                if (key_exists($hookId, $contentBlocksHooks)) {
                    continue;
                }

                $contentBlocksHooks[$hookId] = $getHookName($hookId);
            }
        }

        $sql = 'SELECT DISTINCT(`id_hook`) 
            FROM `' . _DB_PREFIX_ . 'hook_module` 
            WHERE `id_module` = ' . (int) $this->module->id . ' AND `id_shop` = ' . $this->contextShopId;
        $result = Db::getInstance()->executeS($sql);

        if (!is_array($result)) {
            throw new RuntimeException("Can't update module hooks");
        }

        $moduleHooks = array_map(function (array $row) use ($getHookName): string {
            return $getHookName(intval($row['id_hook']));
        }, $result);

        foreach (array_diff($moduleHooks, $contentBlocksHooks, KJContentBlocks::HOOKS) as $hook) {
            $this->module->unregisterHook($hook);
        }

        foreach (array_diff($contentBlocksHooks, $moduleHooks) as $hook) {
            $this->module->registerHook($hook);
        }
    }
}
