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

use Kaudaj\Module\ContentBlocks\Form\Settings\GeneralConfiguration;
use Kaudaj\Module\ContentBlocks\Repository\ContentBlockLangRepository;
use Kaudaj\Module\ContentBlocks\Repository\ContentBlockRepository;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class KJContentBlocks extends Module
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
    public const HOOKS = [
        'exampleHook',
    ];

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
                `id_content_block` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `id_hook` INT,
                `position` INT
            ) ENGINE=$dbEngine COLLATE=utf8mb4_general_ci;
        ";

        $sql[] = "
            CREATE TABLE IF NOT EXISTS `$dbPrefix" . ContentBlockLangRepository::TABLE_NAME . "` (
                `id_content_block` INT UNSIGNED NOT NULL,
                `id_lang` INT NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `content` TEXT NOT NULL,
                PRIMARY KEY (id_content_block, id_lang),
                FOREIGN KEY (`id_lang`)
                REFERENCES `{$dbPrefix}lang` (`id_lang`)
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
            DROP TABLE IF EXISTS `$dbPrefix" . ContentBlockRepository::TABLE_NAME . '`
        ';

        $sql[] = "
            DROP TABLE IF EXISTS `$dbPrefix" . ContentBlockLangRepository::TABLE_NAME . '`
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
     * Example hook
     *
     * @param array<string, mixed> $params Hook parameters
     */
    public function hookExampleHook(array $params): void
    {
        /* Do anything */
    }
}
