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

use Kaudaj\Module\Blocks\Domain\BlockGroup\Query\GetBlockGroupForEditing;
use Kaudaj\Module\Blocks\Domain\BlockGroup\QueryResult\EditableBlockGroup;
use Kaudaj\Module\Blocks\Form\Type\BlockGroupType;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataProvider\FormDataProviderInterface;

final class BlockGroupFormDataProvider implements FormDataProviderInterface
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
    public function getData($id)
    {
        /** @var EditableBlockGroup $editableBlockGroup */
        $editableBlockGroup = $this->queryBus->handle(new GetBlockGroupForEditing($id));

        $localizedNames = [];
        foreach ($editableBlockGroup->getLocalizedNames() as $langId => $name) {
            $localizedNames[$langId] = $name->getValue();
        }

        $hooksIds = [];
        foreach ($editableBlockGroup->getHooksIds() as $hookId) {
            $hooksIds[] = $hookId->getValue();
        }

        return [
            BlockGroupType::FIELD_HOOKS => $hooksIds,
            BlockGroupType::FIELD_NAME => $localizedNames,
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
