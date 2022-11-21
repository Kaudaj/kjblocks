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

use Kaudaj\Module\Blocks\BlockFormMapperInterface;
use Kaudaj\Module\Blocks\BlockTypeProvider;
use Kaudaj\Module\Blocks\Domain\Block\Command\AddBlockCommand;
use Kaudaj\Module\Blocks\Domain\Block\Command\EditBlockCommand;
use Kaudaj\Module\Blocks\Form\Type\BlockType;
use PrestaShop\PrestaShop\Adapter\ContainerFinder;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataHandler\FormDataHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class BlockFormDataHandler implements FormDataHandlerInterface
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(CommandBusInterface $commandBus, LegacyContext $legacyContext)
    {
        $this->commandBus = $commandBus;

        $containerFinder = new ContainerFinder($legacyContext->getContext());
        $this->container = $containerFinder->getContainer();
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $type = strval($data[BlockType::FIELD_TYPE]);

        $options = null;
        if (is_array($data[BlockType::FIELD_OPTIONS])) {
            $options = $this->buildBlockOptions($type, $data[BlockType::FIELD_OPTIONS]);
        }

        $addBlockCommand = (new AddBlockCommand())
            ->setHooksIds(is_array($data[BlockType::FIELD_HOOKS]) ? $data[BlockType::FIELD_HOOKS] : [])
            ->setLocalizedNames(array_filter($data[BlockType::FIELD_NAME])) /* @phpstan-ignore-line */
            ->setType($type)
            ->setOptions($options);

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
        $type = strval($data[BlockType::FIELD_TYPE]);

        $options = null;
        if (is_array($data[BlockType::FIELD_OPTIONS])) {
            $options = $this->buildBlockOptions($type, $data[BlockType::FIELD_OPTIONS]);
        }

        $editBlockCommand = (new EditBlockCommand((int) $id))
            ->setHooksIds(is_array($data[BlockType::FIELD_HOOKS]) ? $data[BlockType::FIELD_HOOKS] : [])
            ->setLocalizedNames(array_filter($data[BlockType::FIELD_NAME])) /* @phpstan-ignore-line */
            ->setType(strval($data[BlockType::FIELD_TYPE]))
            ->setOptions($options)
        ;

        $this->commandBus->handle($editBlockCommand);
    }

    /**
     * @param array<string, mixed> $formOptions
     */
    private function buildBlockOptions(string $type, array $formOptions): ?string
    {
        $block = BlockTypeProvider::getBlockType($type);

        if (!$block) {
            return null;
        }

        /** @var BlockFormMapperInterface */
        $blockFormHandler = $this->container->get($block->getFormMapper());

        return json_encode($blockFormHandler->mapToBlockOptions(array_filter($formOptions))) ?: null;
    }
}
