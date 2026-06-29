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

// Integration bootstrap: boot PrestaShop's legacy environment (Db, Configuration,
// Hook, the Symfony container, Doctrine...) via config.inc.php. Requires
// _PS_ROOT_DIR_ and an installed shop (the test bench provides one). Without it,
// integration tests skip themselves via their setUp().
$root = getenv('_PS_ROOT_DIR_');
if (is_string($root) && $root !== '' && is_file($root . '/config/config.inc.php')) {
    if (!defined('_PS_ADMIN_DIR_')) {
        define('_PS_ADMIN_DIR_', $root . '/admin-dev');
    }
    require_once $root . '/config/config.inc.php';
}

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
