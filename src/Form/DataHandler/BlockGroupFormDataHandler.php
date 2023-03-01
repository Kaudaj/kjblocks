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

namespace Kaudaj\Module\Blocks\Form\DataHandler;

use Kaudaj\Module\Blocks\Domain\BlockGroup\Command\AddBlockGroupCommand;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Command\EditBlockGroupCommand;
use Kaudaj\Module\Blocks\Domain\BlockGroup\ValueObject\BlockGroupId;
use Kaudaj\Module\Blocks\Form\Type\BlockGroupType;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataHandler\FormDataHandlerInterface;

final class BlockGroupFormDataHandler implements FormDataHandlerInterface
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    public function __construct(CommandBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $addBlockGroupCommand = (new AddBlockGroupCommand())
            ->setHooksIds(is_array($data[BlockGroupType::FIELD_HOOKS]) ? $data[BlockGroupType::FIELD_HOOKS] : [])
            ->setLocalizedNames(array_filter($data[BlockGroupType::FIELD_NAME])) /* @phpstan-ignore-line */
        ;

        /** @var BlockGroupId */
        $blockGroupId = $this->commandBus->handle($addBlockGroupCommand);
        $blockGroupId = $blockGroupId->getValue();

        return $blockGroupId;
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $data
     */
    public function update($id, array $data): void
    {
        $editBlockGroupCommand = (new EditBlockGroupCommand((int) $id))
            ->setHooksIds(is_array($data[BlockGroupType::FIELD_HOOKS]) ? $data[BlockGroupType::FIELD_HOOKS] : [])
            ->setLocalizedNames(array_filter($data[BlockGroupType::FIELD_NAME])) /* @phpstan-ignore-line */
        ;

        $this->commandBus->handle($editBlockGroupCommand);
    }
}
