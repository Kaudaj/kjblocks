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

use Kaudaj\Module\Blocks\Domain\Block\Command\AddBlockCommand;
use Kaudaj\Module\Blocks\Domain\Block\Command\EditBlockCommand;
use Kaudaj\Module\Blocks\Form\Type\BlockType;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataHandler\FormDataHandlerInterface;

final class BlockFormDataHandler implements FormDataHandlerInterface
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
        $addBlockCommand = (new AddBlockCommand())
            ->setHooksIds(is_array($data[BlockType::FIELD_HOOKS]) ? $data[BlockType::FIELD_HOOKS] : [])
            ->setLocalizedNames(array_filter($data[BlockType::FIELD_NAME])) /* @phpstan-ignore-line */
            ->setLocalizedContents(array_filter($data[BlockType::FIELD_CONTENT])) /* @phpstan-ignore-line */
        ;

        $blockId = $this->commandBus->handle($addBlockCommand);

        return $blockId->getValue();
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $data
     */
    public function update($id, array $data): void
    {
        $editBlockCommand = (new EditBlockCommand((int) $id))
            ->setHooksIds(is_array($data[BlockType::FIELD_HOOKS]) ? $data[BlockType::FIELD_HOOKS] : [])
            ->setLocalizedNames(array_filter($data[BlockType::FIELD_NAME])) /* @phpstan-ignore-line */
            ->setLocalizedContents(array_filter($data[BlockType::FIELD_CONTENT])) /* @phpstan-ignore-line */
        ;

        $this->commandBus->handle($editBlockCommand);
    }
}
