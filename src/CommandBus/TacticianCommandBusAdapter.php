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

namespace Kaudaj\Module\Blocks\CommandBus;

use League\Tactician\CommandBus;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Adapts the module's own Tactician bus (used in front-office context, where the
 * core command bus is not available) to PrestaShop's CommandBusInterface.
 *
 * Replaces the core PrestaShop\PrestaShop\Core\CommandBus\TacticianCommandBusAdapter,
 * which was removed in PrestaShop 9 when the core switched from Tactician to Symfony
 * Messenger. Tactician is still bundled with this module for the front-office bus.
 */
final class TacticianCommandBusAdapter implements CommandBusInterface
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * {@inheritdoc}
     *
     * @param object $command
     *
     * @return mixed
     */
    public function handle($command)
    {
        return $this->commandBus->handle($command);
    }
}
