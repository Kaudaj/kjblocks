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

namespace Kaudaj\Module\Blocks\Builder;

use Kaudaj\Module\Blocks\Domain\Block\Query\GetBlock;
use Kaudaj\Module\Blocks\Domain\Block\QueryHandler\GetBlockHandler;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Query\GetBlockGroup;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Query\GetBlockGroupsByHook;
use Kaudaj\Module\Blocks\Domain\BlockGroup\QueryHandler\GetBlockGroupHandler;
use Kaudaj\Module\Blocks\Domain\BlockGroup\QueryHandler\GetBlockGroupsByHookHandler;
use League\Tactician\CommandBus;
use League\Tactician\Setup\QuickStart;

/**
 * Class CommandBusBuilder
 * Used to build command bus in front-office context
 */
class CommandBusBuilder
{
    /**
     * @var array<string, mixed>
     *
     * Command class => CommandHandler instance
     */
    private $commandHandlers = [];

    public function __construct(
        GetBlockGroupsByHookHandler $getBlockGroupsByHookHandler,
        GetBlockHandler $getBlockHandler,
        GetBlockGroupHandler $getBlockGroupHandler
    ) {
        $this->commandHandlers = [
            GetBlockGroupsByHook::class => $getBlockGroupsByHookHandler,
            GetBlock::class => $getBlockHandler,
            GetBlockGroup::class => $getBlockGroupHandler,
        ];
    }

    public function build(): CommandBus
    {
        return QuickStart::create($this->commandHandlers);
    }
}
