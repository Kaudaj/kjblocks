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

use Kaudaj\Module\Blocks\BlockTypeProvider;
use Kaudaj\Module\Blocks\Domain\Block\Query\GetBlocksByHook;
use Kaudaj\Module\Blocks\Entity\Block;
use Kaudaj\Module\Blocks\Repository\BlockHookRepository;
use Kaudaj\Module\Blocks\Repository\BlockRepository;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class KJBlocks extends Module implements WidgetInterface
{
    /**
     * @var string[] Hooks to register
     */
    public const HOOKS = [];

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
        ];

        // $blcoks = $this->getAvailableBlocks();
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
            CREATE TABLE IF NOT EXISTS `$dbPrefix" . BlockHookRepository::TABLE_NAME . "` (
                `id_block` INT UNSIGNED NOT NULL,
                `id_hook` INT UNSIGNED NOT NULL,
                `position` INT UNSIGNED NOT NULL,
                PRIMARY KEY (id_block, id_hook),
                FOREIGN KEY (`id_block`)
                REFERENCES `{$dbPrefix}" . BlockRepository::TABLE_NAME . "` (`id_block`)
                ON DELETE CASCADE,
                FOREIGN KEY (`id_hook`)
                REFERENCES `{$dbPrefix}hook` (`id_hook`)
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
            DROP TABLE IF EXISTS `$dbPrefix" . BlockHookRepository::TABLE_NAME . '`
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
     * @param string $hookName
     * @param array<string, mixed> $configuration
     */
    public function renderWidget($hookName, array $configuration): string
    {
        $render = '';

        try {
            /** @var Block[] */
            $blocks = $this->getCommandBus()->handle(new GetBlocksByHook($hookName));

            foreach ($blocks as $block) {
                $render .= $this->renderBlock($block);
            }
        } catch (Exception $e) {
            return '';
        }

        return $render;
    }

    private function renderBlock(Block $blockEntity): string
    {
        $blockType = $blockEntity->getType();
        $block = BlockTypeProvider::getBlockType($blockType);

        if (!$block) {
            throw new RuntimeException("Can't retrieve $blockType block");
        }

        $block = clone $block;

        if (!$blockEntity->getOptions()) {
            return $block->render();
        }

        $options = json_decode($blockEntity->getOptions(), true) ?: [];
        if (!is_array($options)) {
            return $block->render();
        }

        $multiLangOptions = $block->getMultiLangOptions();

        $defaultLangId = (new Configuration())->getInt('PS_LANG_DEFAULT');
        $contextLangId = intval($this->context->language->id);

        foreach ($options as $option => $value) {
            $option = strval($option);

            if (!(in_array($option, $multiLangOptions) && is_array($value))) {
                continue;
            }

            if (key_exists(0, $value)) {
                foreach ($value as $key => $langValues) {
                    $options[$option][$key] = $this->getContextLangValue($langValues, $defaultLangId, $contextLangId);
                }
            } else {
                $options[$option] = $this->getContextLangValue($value, $defaultLangId, $contextLangId);
            }
        }

        $resolver = new OptionsResolver();
        $block->configureOptions($resolver);
        $options = $resolver->resolve($options);

        return $block->render($options);
    }

    /**
     * @param array<int, mixed> $langValues
     *
     * @return mixed
     */
    private function getContextLangValue(array $langValues, int $defaultLangId, int $contextLangId)
    {
        if (key_exists($contextLangId, $langValues)) {
            return $langValues[$contextLangId];
        } elseif (key_exists($defaultLangId, $langValues)) {
            return $langValues[$defaultLangId];
        }

        return null;
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
