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

namespace Kaudaj\Module\Blocks\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Kaudaj\Module\Blocks\Entity\BlockGroup;
use Kaudaj\Module\Blocks\Repository\BlockGroupRepository;

class BlockGroupHooksUpdater
{
    /**
     * @var BlockGroupRepository
     */
    private $entityRepository;

    /**
     * @var \Module
     */
    private $module;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityRepository = $entityManager->getRepository(BlockGroup::class);

        $module = \Module::getInstanceByName('kjblocks');
        if (!($module instanceof \KJBlocks)) {
            throw new \RuntimeException("Can't update module hooks");
        }

        $this->module = $module;
    }

    public function postUpdate(BlockGroup $blockGroup, LifecycleEventArgs $event): void
    {
        $this->updateRegisteredHooks();
    }

    public function postPersist(BlockGroup $blockGroup, LifecycleEventArgs $event): void
    {
        $this->updateRegisteredHooks();
    }

    public function postRemove(BlockGroup $blockGroup, LifecycleEventArgs $event): void
    {
        $this->updateRegisteredHooks();
    }

    private function updateRegisteredHooks(): void
    {
        $getHookName = function (int $hookId): string {
            return \Hook::getNameById($hookId);
        };

        $blocks = $this->entityRepository->findAll();

        $blocksHooks = [];
        foreach ($blocks as $block) {
            foreach ($block->getBlockGroupHooks() as $blockGroupHook) {
                $hookId = $blockGroupHook->getHookId();

                if (key_exists($hookId, $blocksHooks)) {
                    continue;
                }

                $blocksHooks[$hookId] = $getHookName($hookId);
            }
        }

        $sql = 'SELECT DISTINCT(`id_hook`) 
            FROM `' . _DB_PREFIX_ . 'hook_module` 
            WHERE `id_module` = ' . (int) $this->module->id;

        $result = \Db::getInstance()->executeS($sql);

        if (!is_array($result)) {
            throw new \RuntimeException("Can't update module hooks");
        }

        $moduleHooks = array_map(function (array $row) use ($getHookName): string {
            return $getHookName(intval($row['id_hook']));
        }, $result);

        foreach (array_diff($moduleHooks, $blocksHooks, \KJBlocks::HOOKS) as $hook) {
            $this->module->unregisterHook($hook);
        }

        foreach (array_diff($blocksHooks, $moduleHooks) as $hook) {
            $this->module->registerHook($hook);
        }
    }
}
