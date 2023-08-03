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

use Kaudaj\Module\Blocks\Repository\BlockRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_2_0(Module $module): bool
{
    $sql = [];
    $dbPrefix = _DB_PREFIX_;

    $sql[] = "
        ALTER TABLE `$dbPrefix" . BlockRepository::SHOP_TABLE_NAME . '`
        ADD `options` JSON;
    ';

    $sql[] = "
        INSERT INTO `$dbPrefix" . BlockRepository::SHOP_TABLE_NAME . "` (`id_block`, `options`)
        SELECT blockTable.`id_block`, blockTable.`options`
        FROM `$dbPrefix" . BlockRepository::TABLE_NAME . '` AS blockTable
        ON DUPLICATE KEY UPDATE
        `options` = blockTable.`options`;
    ';

    $sql[] = "
        ALTER TABLE `$dbPrefix" . BlockRepository::TABLE_NAME . '`
        DROP COLUMN `options`;
    ';

    $result = true;
    foreach ($sql as $query) {
        $result = $result && Db::getInstance()->execute($query);
    }

    return $result;
}
