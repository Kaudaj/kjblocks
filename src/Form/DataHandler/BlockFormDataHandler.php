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
use Kaudaj\Module\Blocks\Domain\Block\Exception\BlockException;
use Kaudaj\Module\Blocks\Domain\Block\ValueObject\BlockId;
use Kaudaj\Module\Blocks\Form\Type\BlockType;
use Kaudaj\Module\Blocks\Form\Type\BlockTypeType;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataHandler\FormDataHandlerInterface;

final class BlockFormDataHandler implements FormDataHandlerInterface
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @var BlockTypeFormDataHandler
     */
    private $blockTypeFormDataHandler;

    public function __construct(CommandBusInterface $commandBus, BlockTypeFormDataHandler $blockTypeFormDataHandler)
    {
        $this->commandBus = $commandBus;
        $this->blockTypeFormDataHandler = $blockTypeFormDataHandler;
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        if (!is_array($data[BlockType::FIELD_TYPE])) {
            throw new BlockException('Block type has not been found.');
        }

        $type = strval($data[BlockType::FIELD_TYPE][BlockTypeType::FIELD_TYPE]);

        $addBlockCommand = (new AddBlockCommand())
            ->setHooksIds(is_array($data[BlockType::FIELD_HOOKS]) ? $data[BlockType::FIELD_HOOKS] : [])
            ->setLocalizedNames(array_filter($data[BlockType::FIELD_NAME])) /* @phpstan-ignore-line */
            ->setType($type)
        ;

        /** @var BlockId */
        $blockId = $this->commandBus->handle($addBlockCommand);
        $blockId = $blockId->getValue();

        $options = null;
        if (is_array($data[BlockType::FIELD_TYPE][BlockTypeType::FIELD_OPTIONS])) {
            $options = $this->blockTypeFormDataHandler->buildBlockOptions(
                $blockId,
                'block',
                BlockType::FIELD_TYPE,
                $type,
                $data[BlockType::FIELD_TYPE][BlockTypeType::FIELD_OPTIONS]
            );
        }

        $editBlockCommand = (new EditBlockCommand($blockId))
            ->setOptions(json_encode($options) ?: null);

        $this->commandBus->handle($editBlockCommand);

        return $blockId;
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $data
     */
    public function update($id, array $data): void
    {
        if (!is_array($data[BlockType::FIELD_TYPE])) {
            throw new BlockException('Block type has not been found.');
        }

        $type = strval($data[BlockType::FIELD_TYPE][BlockTypeType::FIELD_TYPE]);

        $options = null;
        if (is_array($data[BlockType::FIELD_TYPE][BlockType::FIELD_OPTIONS])) {
            $options = $this->blockTypeFormDataHandler->buildBlockOptions(
                $id,
                'block',
                BlockType::FIELD_TYPE,
                $type,
                $data[BlockType::FIELD_TYPE][BlockTypeType::FIELD_OPTIONS]
            );
        }

        $editBlockCommand = (new EditBlockCommand((int) $id))
            ->setHooksIds(is_array($data[BlockType::FIELD_HOOKS]) ? $data[BlockType::FIELD_HOOKS] : [])
            ->setLocalizedNames(array_filter($data[BlockType::FIELD_NAME])) /* @phpstan-ignore-line */
            ->setType($type)
            ->setOptions(json_encode($options) ?: null);

        $this->commandBus->handle($editBlockCommand);
    }
}
