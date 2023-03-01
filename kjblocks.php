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

if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}

use Kaudaj\Module\Blocks\BlockInterface;
use Kaudaj\Module\Blocks\BlockRenderer;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Query\GetBlockGroupsByHook;
use Kaudaj\Module\Blocks\Entity\Block;
use Kaudaj\Module\Blocks\Entity\BlockGroup;
use Kaudaj\Module\Blocks\Entity\BlockGroupBlock;
use Kaudaj\Module\Blocks\Repository\BlockGroupBlockRepository;
use Kaudaj\Module\Blocks\Repository\BlockGroupHookRepository;
use Kaudaj\Module\Blocks\Repository\BlockGroupRepository;
use Kaudaj\Module\Blocks\Repository\BlockRepository;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class KJBlocks extends Module implements WidgetInterface
{
    /**
     * @var string[] Hooks to register
     */
    public const HOOKS = [
        'actionGetBlockTypes',
    ];

    public function __construct()
    {
        $this->name = 'kjblocks';
        $this->tab = 'others';
        $this->version = '1.0.0';
        $this->author = 'Kaudaj';
        $this->ps_versions_compliancy = ['min' => '1.7.8.0', 'max' => _PS_VERSION_];

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Blocks', [], 'Modules.Kjblocks.Admin');
        $this->description = $this->trans(
            <<<EOF
        Add your blocks where you want them.
EOF
            ,
            [],
            'Modules.Kjblocks.Admin'
        );

        $this->tabs = [
            [
                'name' => 'KJBlocks',
                'class_name' => 'KJBlocks',
                'parent_class_name' => 'AdminParentModulesSf',
                'visible' => false,
                'wording' => 'KJBlocks',
                'wording_domain' => 'Modules.Kjblocks.Admin',
            ],
            [
                'name' => 'Blocks',
                'class_name' => 'KJBlocksBlock',
                'route_name' => 'kj_blocks_blocks_index',
                'parent_class_name' => 'KJBlocks',
                'wording' => 'Blocks',
                'wording_domain' => 'Modules.Kjblocks.Admin',
            ],
            [
                'name' => 'Block groups',
                'class_name' => 'KJBlocksBlockGroup',
                'route_name' => 'kj_blocks_block_groups_index',
                'parent_class_name' => 'KJBlocks',
                'wording' => 'Block groups',
                'wording_domain' => 'Modules.Kjblocks.Admin',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function install(): bool
    {
        return parent::install()
            && $this->registerHook(self::HOOKS)
            && $this->installTables()
        ;
    }

    /**
     * Install database tables
     *
     * @return bool
     */
    private function installTables()
    {
        $sql = [];
        $dbPrefix = pSQL(_DB_PREFIX_);
        $dbEngine = pSQL(_MYSQL_ENGINE_);

        $sql[] = "
            CREATE TABLE IF NOT EXISTS `$dbPrefix" . BlockRepository::TABLE_NAME . "` (
                `id_block` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `type` VARCHAR(255) NOT NULL,
                `options` JSON
            ) ENGINE=$dbEngine COLLATE=utf8mb4_general_ci;
        ";

        $sql[] = "
            CREATE TABLE IF NOT EXISTS `$dbPrefix" . BlockRepository::LANG_TABLE_NAME . "` (
                `id_block` INT UNSIGNED NOT NULL,
                `id_lang` INT NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                PRIMARY KEY (id_block, id_lang),
                FOREIGN KEY (`id_block`)
                REFERENCES `{$dbPrefix}" . BlockRepository::TABLE_NAME . "` (`id_block`)
                ON DELETE CASCADE,
                FOREIGN KEY (`id_lang`)
                REFERENCES `{$dbPrefix}lang` (`id_lang`)
                ON DELETE CASCADE
            ) ENGINE=$dbEngine COLLATE=utf8mb4_general_ci;
        ";

        $sql[] = "
            CREATE TABLE IF NOT EXISTS `$dbPrefix" . BlockGroupRepository::TABLE_NAME . "` (
                `id_block_group` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            ) ENGINE=$dbEngine COLLATE=utf8mb4_general_ci;
        ";

        $sql[] = "
            CREATE TABLE IF NOT EXISTS `$dbPrefix" . BlockGroupRepository::LANG_TABLE_NAME . "` (
                `id_block_group` INT UNSIGNED NOT NULL,
                `id_lang` INT NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                PRIMARY KEY (id_block_group, id_lang),
                FOREIGN KEY (`id_block_group`)
                REFERENCES `{$dbPrefix}" . BlockGroupRepository::TABLE_NAME . "` (`id_block_group`)
                ON DELETE CASCADE,
                FOREIGN KEY (`id_lang`)
                REFERENCES `{$dbPrefix}lang` (`id_lang`)
                ON DELETE CASCADE
            ) ENGINE=$dbEngine COLLATE=utf8mb4_general_ci;
        ";

        $sql[] = "
            CREATE TABLE IF NOT EXISTS `$dbPrefix" . BlockGroupHookRepository::TABLE_NAME . "` (
                `id_block_group` INT UNSIGNED NOT NULL,
                `id_hook` INT UNSIGNED NOT NULL,
                `position` INT UNSIGNED NOT NULL,
                PRIMARY KEY (id_block_group, id_hook),
                FOREIGN KEY (`id_block_group`)
                REFERENCES `{$dbPrefix}" . BlockGroupRepository::TABLE_NAME . "` (`id_block_group`)
                ON DELETE CASCADE,
                FOREIGN KEY (`id_hook`)
                REFERENCES `{$dbPrefix}hook` (`id_hook`)
                ON DELETE CASCADE
            ) ENGINE=$dbEngine COLLATE=utf8mb4_general_ci;
        ";

        $sql[] = "
            CREATE TABLE IF NOT EXISTS `$dbPrefix" . BlockGroupBlockRepository::TABLE_NAME . "` (
                `id_block` INT UNSIGNED NOT NULL,
                `id_block_group` INT UNSIGNED NOT NULL,
                `position` INT UNSIGNED NOT NULL,
                PRIMARY KEY (id_block, id_block_group),
                FOREIGN KEY (`id_block`)
                REFERENCES `{$dbPrefix}" . BlockRepository::TABLE_NAME . "` (`id_block`)
                ON DELETE CASCADE,
                FOREIGN KEY (`id_block_group`)
                REFERENCES `{$dbPrefix}" . BlockGroupRepository::TABLE_NAME . "` (`id_block_group`)
                ON DELETE CASCADE
            ) ENGINE=$dbEngine COLLATE=utf8mb4_general_ci;
        ";

        $result = true;
        foreach ($sql as $query) {
            $result = $result && Db::getInstance()->execute($query);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(): bool
    {
        return parent::uninstall()
            && $this->uninstallTables()
        ;
    }

    /**
     * Uninstall database tables
     *
     * @return bool
     */
    private function uninstallTables()
    {
        $sql = [];
        $dbPrefix = pSQL(_DB_PREFIX_);

        $sql[] = "
            DROP TABLE IF EXISTS `$dbPrefix" . BlockGroupBlockRepository::TABLE_NAME . '`
        ';

        $sql[] = "
            DROP TABLE IF EXISTS `$dbPrefix" . BlockGroupHookRepository::TABLE_NAME . '`
        ';

        $sql[] = "
            DROP TABLE IF EXISTS `$dbPrefix" . BlockGroupRepository::LANG_TABLE_NAME . '`
        ';

        $sql[] = "
            DROP TABLE IF EXISTS `$dbPrefix" . BlockGroupRepository::TABLE_NAME . '`
        ';

        $sql[] = "
            DROP TABLE IF EXISTS `$dbPrefix" . BlockRepository::LANG_TABLE_NAME . '`
        ';

        $sql[] = "
            DROP TABLE IF EXISTS `$dbPrefix" . BlockRepository::TABLE_NAME . '`
        ';

        $result = true;
        foreach ($sql as $query) {
            $result = $result && Db::getInstance()->execute($query);
        }

        return $result;
    }

    /**
     * Get module configuration page content
     */
    public function getContent(): void
    {
        $container = SymfonyContainer::getInstance();

        if ($container != null) {
            /** @var UrlGeneratorInterface */
            $router = $container->get('router');

            Tools::redirectAdmin($router->generate('kj_blocks_blocks_index'));
        }
    }

    /**
     * @return string[]
     */
    public function hookActionGetBlockTypes(): array
    {
        return [
            'kaudaj.module.blocks.block.container',
            'kaudaj.module.blocks.block.text',
        ];
    }

    /**
     * @param string $hookName
     * @param array<string, mixed> $configuration
     */
    public function renderWidget($hookName, array $configuration): string
    {
        $render = '';

        try {
            /** @var BlockRenderer<BlockInterface> */
            $blockRenderer = $this->get('kaudaj.module.blocks.block_renderer');

            /** @var BlockGroup[] */
            $blockGroups = $this->getCommandBus()->handle(new GetBlockGroupsByHook($hookName));

            foreach ($blockGroups as $blockGroup) {
                $blockGroupBlocks = $blockGroup->getBlockGroupBlocks()->getValues();

                usort($blockGroupBlocks, function (BlockGroupBlock $blockGroupBlock1, BlockGroupBlock $blockGroupBlock2): int {
                    if ($blockGroupBlock1->getPosition() == $blockGroupBlock2->getPosition()) {
                        return 0;
                    }

                    return ($blockGroupBlock1->getPosition() < $blockGroupBlock2->getPosition()) ? -1 : 1;
                });

                $blocks = array_map(function (BlockGroupBlock $blockGroupBlock): Block {
                    return $blockGroupBlock->getBlock();
                }, $blockGroupBlocks);

                /** @var BlockRenderer<BlockInterface> */
                $blockRenderer = $this->get('kaudaj.module.blocks.block_renderer');

                foreach ($blocks as $block) {
                    $render .= $blockRenderer->renderBlock($block);
                }
            }
        } catch (Exception $e) {
            // return '';
            throw $e;
        }

        return $render;
    }

    /**
     * @param string $hookName
     * @param array<string, mixed> $configuration
     *
     * @return array<string, mixed>
     */
    public function getWidgetVariables($hookName, array $configuration): array
    {
        return [];
    }

    private function getCommandBus(): CommandBusInterface
    {
        $commandBus = $this->getService(
            'prestashop.core.command_bus',
            'kaudaj.module.blocks.command_bus'
        );

        if (!($commandBus instanceof CommandBusInterface)) {
            throw new RuntimeException("Can't retrieve command bus");
        }

        return $commandBus;
    }

    /**
     * @return mixed
     */
    private function getService(string $backService, string $frontService)
    {
        $object = false;

        try {
            $object = $this->get($backService);
        } catch (ServiceNotFoundException $e) {
        }

        if (!$object) {
            $object = $this->get($frontService);
        }

        if (!$object) {
            throw new RuntimeException('Container not available.');
        }

        return $object;
    }
}
