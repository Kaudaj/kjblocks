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
use Kaudaj\Module\Blocks\Domain\BlockGroup\Command\AddBlockGroupCommand;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Query\GetBlockGroupByName;
use Kaudaj\Module\Blocks\Domain\BlockGroup\ValueObject\BlockGroupId;
use Kaudaj\Module\Blocks\Entity\BlockGroup;
use Kaudaj\Module\Blocks\Form\Type\BlockType;
use Kaudaj\Module\Blocks\Form\Type\BlockTypeType;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Adapter\Shop\Context as ShopContext;
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

    /**
     * @var ShopContext
     */
    private $shopContext;

    /**
     * @var LegacyContext
     */
    private $legacyContext;

    public function __construct(
        CommandBusInterface $commandBus,
        BlockTypeFormDataHandler $blockTypeFormDataHandler,
        ShopContext $shopContext,
        LegacyContext $legacyContext
    ) {
        $this->commandBus = $commandBus;
        $this->blockTypeFormDataHandler = $blockTypeFormDataHandler;
        $this->shopContext = $shopContext;
        $this->legacyContext = $legacyContext;
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

        $type = (string) $data[BlockType::FIELD_TYPE][BlockTypeType::FIELD_TYPE];
        $groups = is_array($data[BlockType::FIELD_GROUPS]) ? $data[BlockType::FIELD_GROUPS] : [];

        $addBlockCommand = (new AddBlockCommand())
            ->setBlockGroupsIds($this->getBlockGroupIds($groups))
            ->setLocalizedNames(array_filter($data[BlockType::FIELD_NAME]))
            ->setType($type)
            ->setShopConstraint($this->shopContext->getShopConstraint())
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
            ->setOptions(json_encode($options) ?: null)
            ->setShopConstraint($this->shopContext->getShopConstraint())
        ;

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

        $type = (string) $data[BlockType::FIELD_TYPE][BlockTypeType::FIELD_TYPE];

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

        $groups = is_array($data[BlockType::FIELD_GROUPS]) ? $data[BlockType::FIELD_GROUPS] : [];

        $editBlockCommand = (new EditBlockCommand((int) $id))
            ->setBlockGroupsIds($this->getBlockGroupIds($groups))
            ->setLocalizedNames(array_filter($data[BlockType::FIELD_NAME]))
            ->setType($type)
            ->setOptions(json_encode($options) ?: null)
            ->setShopConstraint($this->shopContext->getShopConstraint());

        $this->commandBus->handle($editBlockCommand);

        $this->legacyContext->getSmarty()->clearCache(null, 'kjblocks'); // @phpstan-ignore-line
    }

    /**
     * @param string[] $groups
     *
     * @return int[]
     */
    private function getBlockGroupIds(array $groups): array
    {
        $blockGroupIds = [];

        foreach ($groups as $group) {
            list($type, $id) = explode('-', $group, 2);
            $id = (int) $id;

            if ($type === 'group') {
                $blockGroupIds[] = $id;

                continue;
            }

            $hookName = \Hook::getNameById((int) $id);

            /** @var BlockGroup|null */
            $blockGroup = $this->commandBus->handle(new GetBlockGroupByName($hookName));

            if ($blockGroup) {
                $blockGroupIds[] = $blockGroup->getId();

                continue;
            }

            $defaultLangId = (new Configuration())->getInt('PS_LANG_DEFAULT');

            /** @var BlockGroupId */
            $id = $this->commandBus->handle((new AddBlockGroupCommand())
                ->setHooksIds([$id])
                ->setLocalizedNames([$defaultLangId => $hookName])
            );

            $blockGroupIds[] = $id->getValue();
        }

        return $blockGroupIds;
    }
}
