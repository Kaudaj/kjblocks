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
use Kaudaj\Module\Blocks\Domain\Block\ValueObject\BlockId;
use Kaudaj\Module\Blocks\Form\Type\BlockType;
use PrestaShop\PrestaShop\Adapter\ContainerFinder;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataHandler\FormDataHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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

    /**
     * @var BlockTypeProvider
     */
    private $blockTypeProvider;

    public function __construct(CommandBusInterface $commandBus, LegacyContext $legacyContext, BlockTypeProvider $blockTypeProvider)
    {
        $this->commandBus = $commandBus;

        $containerFinder = new ContainerFinder($legacyContext->getContext());
        $this->container = $containerFinder->getContainer();

        $this->blockTypeProvider = $blockTypeProvider;
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $type = strval($data[BlockType::FIELD_TYPE]);

        $addBlockCommand = (new AddBlockCommand())
            ->setHooksIds(is_array($data[BlockType::FIELD_HOOKS]) ? $data[BlockType::FIELD_HOOKS] : [])
            ->setLocalizedNames(array_filter($data[BlockType::FIELD_NAME])) /* @phpstan-ignore-line */
            ->setType($type)
        ;

        /** @var BlockId */
        $blockId = $this->commandBus->handle($addBlockCommand);
        $blockId = $blockId->getValue();

        $options = null;
        if (is_array($data[BlockType::FIELD_OPTIONS])) {
            $options = $this->buildBlockOptions($blockId, $type, $data[BlockType::FIELD_OPTIONS]);
        }

        $editBlockCommand = (new EditBlockCommand($blockId))
            ->setOptions($options);

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
        $type = strval($data[BlockType::FIELD_TYPE]);

        $options = null;
        if (is_array($data[BlockType::FIELD_OPTIONS])) {
            $options = $this->buildBlockOptions($id, $type, $data[BlockType::FIELD_OPTIONS]);
        }

        $editBlockCommand = (new EditBlockCommand((int) $id))
            ->setHooksIds(is_array($data[BlockType::FIELD_HOOKS]) ? $data[BlockType::FIELD_HOOKS] : [])
            ->setLocalizedNames(array_filter($data[BlockType::FIELD_NAME])) /* @phpstan-ignore-line */
            ->setType(strval($data[BlockType::FIELD_TYPE]))
            ->setOptions($options);

        $this->commandBus->handle($editBlockCommand);
    }

    /**
     * @param array<string, mixed> $formOptions
     */
    private function buildBlockOptions(int $blockId, string $type, array $formOptions): ?string
    {
        $block = $this->blockTypeProvider->getBlockType($type);

        if (!$block) {
            return null;
        }

        /** @var BlockFormMapperInterface */
        $blockFormHandler = $this->container->get($block->getFormMapper());

        // Filter old options from previous block type

        /** @var RequestStack */
        $requestStack = $this->container->get('request_stack');
        $currentRequest = $requestStack->getCurrentRequest();

        if ($currentRequest !== null) {
            $requestBlockParam = $currentRequest->request->get('block') ?: [];
            $filesBlockParam = $currentRequest->files->get('block') ?: [];

            $requestOptions = [];

            if (is_array($requestBlockParam) && key_exists(BlockType::FIELD_OPTIONS, $requestBlockParam)) {
                $requestOptions = $requestBlockParam[BlockType::FIELD_OPTIONS];
            }

            if (is_array($filesBlockParam) && key_exists(BlockType::FIELD_OPTIONS, $filesBlockParam)) {
                $requestOptions = array_merge($requestOptions, $filesBlockParam[BlockType::FIELD_OPTIONS]);
            }

            $formOptions = array_intersect_key($formOptions, $requestOptions);
        }

        $formOptions = $this->array_filter_recursive($formOptions);

        return json_encode($blockFormHandler->mapToBlockOptions($blockId, $formOptions)) ?: null;
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function array_filter_recursive(array $input): array
    {
        foreach ($input as &$value) {
            if (is_array($value)) {
                $value = $this->array_filter_recursive($value);
            }
        }

        return array_filter($input);
    }
}
