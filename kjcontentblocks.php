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

use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Query\GetContentBlocksByHook;
use Kaudaj\Module\ContentBlocks\Entity\ContentBlock;
use Kaudaj\Module\ContentBlocks\Form\Settings\GeneralConfiguration;
use Kaudaj\Module\ContentBlocks\Repository\ContentBlockHookRepository;
use Kaudaj\Module\ContentBlocks\Repository\ContentBlockRepository;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class KJContentBlocks extends Module implements WidgetInterface
{
    /**
     * @var array<string, string> Configuration values
     */
    public const CONFIGURATION_VALUES = [
        GeneralConfiguration::EXAMPLE_SETTING_KEY => 'default_value',
    ];

    /**
     * @var string[] Hooks to register
     */
    public const HOOKS = [];

    public const HOOK_FILTER_CONTENT = 'filterContentBlockContent';
    public const HOOK_KEY_CONTENT = 'content';

    /**
     * @var Configuration<string, mixed> Configuration
     */
    private $configuration;

    public function __construct()
    {
        $this->name = 'kjcontentblocks';
        $this->tab = 'others';
        $this->version = '1.0.0';
        $this->author = 'Kaudaj';
        $this->ps_versions_compliancy = ['min' => '1.7.8.0', 'max' => _PS_VERSION_];

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Content Blocks', [], 'Modules.Kjcontentblocks.Admin');
        $this->description = $this->trans(<<<EOF
        Add your content blocks where you want them.
EOF
            ,
            [],
            'Modules.Kjcontentblocks.Admin'
        );

        $this->tabs = [
            [
                'name' => 'KJContentBlocks',
                'class_name' => 'KJContentBlocks',
                'parent_class_name' => 'AdminParentModulesSf',
                'visible' => false,
                'wording' => 'KJContentBlocks',
                'wording_domain' => 'Modules.Kjcontentblocks.Admin',
            ],
            [
                'name' => 'Content Blocks',
                'class_name' => 'KJContentBlocksContentBlock',
                'route_name' => 'kj_content_blocks_content_blocks_index',
                'parent_class_name' => 'KJContentBlocks',
                'wording' => 'Content Blocks',
                'wording_domain' => 'Modules.Kjcontentblocks.Admin',
            ],
        ];

        $this->configuration = new Configuration();
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
            && $this->installConfiguration()
            && $this->registerHook(self::HOOKS)
            && $this->installTables()
        ;
    }

    /**
     * Install configuration values
     */
    private function installConfiguration(): bool
    {
        try {
            foreach (self::CONFIGURATION_VALUES as $key => $default_value) {
                $this->configuration->set($key, $default_value);
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
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
            CREATE TABLE IF NOT EXISTS `$dbPrefix" . ContentBlockRepository::TABLE_NAME . "` (
                `id_content_block` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            ) ENGINE=$dbEngine COLLATE=utf8mb4_general_ci;
        ";

        $sql[] = "
            CREATE TABLE IF NOT EXISTS `$dbPrefix" . ContentBlockRepository::LANG_TABLE_NAME . "` (
                `id_content_block` INT UNSIGNED NOT NULL,
                `id_lang` INT NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `content` TEXT NOT NULL,
                PRIMARY KEY (id_content_block, id_lang),
                FOREIGN KEY (`id_content_block`)
                REFERENCES `{$dbPrefix}" . ContentBlockRepository::TABLE_NAME . "` (`id_content_block`)
                ON DELETE CASCADE,
                FOREIGN KEY (`id_lang`)
                REFERENCES `{$dbPrefix}lang` (`id_lang`)
                ON DELETE CASCADE
            ) ENGINE=$dbEngine COLLATE=utf8mb4_general_ci;
        ";

        $sql[] = "
            CREATE TABLE IF NOT EXISTS `$dbPrefix" . ContentBlockHookRepository::TABLE_NAME . "` (
                `id_content_block` INT UNSIGNED NOT NULL,
                `id_hook` INT UNSIGNED NOT NULL,
                `position` INT UNSIGNED NOT NULL,
                PRIMARY KEY (id_content_block, id_hook),
                FOREIGN KEY (`id_content_block`)
                REFERENCES `{$dbPrefix}" . ContentBlockRepository::TABLE_NAME . "` (`id_content_block`)
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
            && $this->uninstallConfiguration()
            && $this->uninstallTables()
        ;
    }

    /**
     * Uninstall configuration values
     */
    private function uninstallConfiguration(): bool
    {
        try {
            foreach (array_keys(self::CONFIGURATION_VALUES) as $key) {
                $this->configuration->remove($key);
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
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
            DROP TABLE IF EXISTS `$dbPrefix" . ContentBlockHookRepository::TABLE_NAME . '`
        ';

        $sql[] = "
            DROP TABLE IF EXISTS `$dbPrefix" . ContentBlockRepository::LANG_TABLE_NAME . '`
        ';

        $sql[] = "
            DROP TABLE IF EXISTS `$dbPrefix" . ContentBlockRepository::TABLE_NAME . '`
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

            Tools::redirectAdmin($router->generate('kj_content_blocks_content_blocks_index'));
        }
    }

    /**
     * @param string $hookName
     * @param array<string, mixed> $configuration
     */
    public function renderWidget($hookName, array $configuration): string
    {
        try {
            /** @var ContentBlock[] */
            $contentBlocks = $this->getCommandBus()->handle(new GetContentBlocksByHook($hookName));

            $content = '';
            foreach ($contentBlocks as $contentBlock) {
                $contentBlockLang = $contentBlock->getContentBlockLang($this->context->language->id);

                $content .= ($contentBlockLang !== null ? $contentBlockLang->getContent() : '');
            }
        } catch (Exception $e) {
            return '';
        }

        // TODO: Use core hook dispatcher
        return strval(Hook::exec(self::HOOK_FILTER_CONTENT, [
            self::HOOK_KEY_CONTENT => $content,
        ]));
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
            'kaudaj.module.content_blocks.command_bus'
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
