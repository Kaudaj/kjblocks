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

namespace Kaudaj\Module\Blocks\Form\DataProvider;

use Kaudaj\Module\Blocks\Domain\Block\Query\GetBlockForEditing;
use Kaudaj\Module\Blocks\Domain\Block\QueryResult\EditableBlock;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Query\GetBlockGroup;
use Kaudaj\Module\Blocks\Entity\BlockGroup;
use Kaudaj\Module\Blocks\Form\Type\BlockType;
use Kaudaj\Module\Blocks\Form\Type\BlockTypeType;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataProvider\FormDataProviderInterface;

final class BlockFormDataProvider implements FormDataProviderInterface
{
    /**
     * @var CommandBusInterface
     */
    private $queryBus;

    /**
     * @var BlockTypeFormDataProvider
     */
    private $blockTypeFormDataProvider;

    public function __construct(CommandBusInterface $queryBus, BlockTypeFormDataProvider $blockTypeFormDataProvider)
    {
        $this->queryBus = $queryBus;
        $this->blockTypeFormDataProvider = $blockTypeFormDataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getData($blockId)
    {
        /** @var EditableBlock $editableBlock */
        $editableBlock = $this->queryBus->handle(new GetBlockForEditing($blockId));

        $localizedNames = [];
        foreach ($editableBlock->getLocalizedNames() as $langId => $name) {
            $localizedNames[$langId] = $name->getValue();
        }

        $blockGroupsIds = [];
        foreach ($editableBlock->getBlockGroupsIds() as $blockGroupId) {
            $blockGroupId = $blockGroupId->getValue();

            /** @var BlockGroup */
            $blockGroup = $this->queryBus->handle(new GetBlockGroup($blockGroupId));

            $firstName = $blockGroup->getBlockGroupLangs()->getValues()[0];

            $hookId = \Hook::getIdByName($firstName->getName());
            if ($hookId) {
                $blockGroupsIds[] = "hook-$hookId";
            } else {
                $blockGroupsIds[] = "group-$blockGroupId";
            }
        }

        $type = $editableBlock->getType();
        $optionsJson = $editableBlock->getOptions();

        $formOptions = null;
        if ($optionsJson !== null) {
            $formOptions = $this->blockTypeFormDataProvider->buildFormOptions(
                $optionsJson,
                $editableBlock->getBlockId()->getValue(),
                $type
            );
        }

        return [
            BlockType::FIELD_GROUPS => $blockGroupsIds,
            BlockType::FIELD_NAME => $localizedNames,
            BlockType::FIELD_TYPE => [
                BlockTypeType::FIELD_TYPE => $type,
                BlockTypeType::FIELD_OPTIONS => $formOptions,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultData()
    {
        return [
            BlockType::FIELD_TYPE => [
                BlockTypeType::FIELD_TYPE => 'text',
            ],
        ];
    }
}
