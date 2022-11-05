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
use Kaudaj\Module\Blocks\Form\Type\BlockType;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataProvider\FormDataProviderInterface;

final class BlockFormDataProvider implements FormDataProviderInterface
{
    /**
     * @var CommandBusInterface
     */
    private $queryBus;

    public function __construct(CommandBusInterface $queryBus)
    {
        $this->queryBus = $queryBus;
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

        $localizedContents = [];
        foreach ($editableBlock->getLocalizedContents() as $langId => $name) {
            $localizedContents[$langId] = $name->getValue();
        }

        $hooksIds = [];
        foreach ($editableBlock->getHooksIds() as $hookId) {
            $hooksIds[] = $hookId->getValue();
        }

        return [
            BlockType::FIELD_HOOKS => $hooksIds,
            BlockType::FIELD_NAME => $localizedNames,
            BlockType::FIELD_CONTENT => $localizedContents,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultData()
    {
        return [];
    }
}
