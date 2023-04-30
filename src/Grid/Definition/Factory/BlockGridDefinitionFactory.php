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

namespace Kaudaj\Module\Blocks\Grid\Definition\Factory;

use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\Employee\ContextEmployeeProvider;
use PrestaShop\PrestaShop\Adapter\Shop\Context as ShopContext;
use PrestaShop\PrestaShop\Core\Form\FormChoiceProviderInterface;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\RowActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\RowActionCollectionInterface;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\LinkRowAction;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\PositionColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\AbstractGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\DeleteActionTrait;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Core\Grid\Filter\FilterCollection;
use PrestaShop\PrestaShop\Core\Hook\HookDispatcherInterface;
use PrestaShopBundle\Entity\Repository\AdminFilterRepository;
use PrestaShopBundle\Form\Admin\Type\SearchAndResetType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

final class BlockGridDefinitionFactory extends AbstractGridDefinitionFactory
{
    use DeleteActionTrait;

    public const GRID_ID = 'blocks';

    /**
     * @var FormChoiceProviderInterface
     */
    private $blockGroupBlockChoiceProvider;

    /**
     * @var string|null
     */
    private $blockGroupFilter;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        FormChoiceProviderInterface $blockGroupBlockChoiceProvider,
        AdminFilterRepository $adminFilterRepository,
        ContextEmployeeProvider $contextEmployeeProvider,
        ShopContext $shopContext
    ) {
        parent::__construct($hookDispatcher);

        $this->blockGroupBlockChoiceProvider = $blockGroupBlockChoiceProvider;

        $this->blockGroupFilter = $this->getBlockGroupFilter(
            $adminFilterRepository,
            $contextEmployeeProvider->getId(),
            $shopContext->getContextShopID() ?: 0
        );
    }

    protected function getId()
    {
        return self::GRID_ID;
    }

    protected function getName()
    {
        return $this->trans('Blocks', [], 'Modules.Kjblocks.Admin');
    }

    protected function getColumns()
    {
        $columns = (new ColumnCollection())
            ->add((new DataColumn('id_block'))
                ->setName($this->trans('ID', [], 'Admin.Global'))
                ->setOptions([
                    'field' => 'id_block',
                ])
            )
            ->add((new DataColumn('name'))
                ->setName($this->trans('Name', [], 'Admin.Global'))
                ->setOptions([
                    'field' => 'name',
                ])
            )
            ->add((new DataColumn('type'))
                ->setName($this->trans('Type', [], 'Admin.Global'))
                ->setOptions([
                    'field' => 'type',
                ])
            )
            ->add((new DataColumn('groups'))
                ->setName(
                    $this->blockGroupFilter === null
                        ? $this->trans('Groups', [], 'Admin.Global')
                        : $this->trans('Group', [], 'Admin.Global')
                )
                ->setOptions([
                    'field' => 'groups',
                ])
            )
            ->add((new ActionColumn('actions'))
                ->setName($this->trans('Actions', [], 'Admin.Global'))
                ->setOptions([
                    'actions' => $this->getRowActions(),
                ])
            )
        ;

        if ($this->blockGroupFilter !== null) {
            $positionColumn = (new PositionColumn('position'))
                ->setName($this->trans('Position', [], 'Modules.Kjblocks.Admin'))
                ->setOptions([
                    'id_field' => 'id_block',
                    'position_field' => 'position',
                    'update_method' => 'POST',
                    'update_route' => 'kj_blocks_blocks_update_position',
                ])
            ;

            $columns->addAfter('groups', $positionColumn);
        }

        return $columns;
    }

    private function getRowActions(): RowActionCollectionInterface
    {
        return (new RowActionCollection())
            ->add((new LinkRowAction('edit'))
                ->setName($this->trans('Edit', [], 'Admin.Actions'))
                ->setIcon('edit')
                ->setOptions($this->getRouteOptions('kj_blocks_blocks_edit') + [
                    'clickable_row' => true,
                ])
            )
            ->add(
                $this->buildDeleteAction(
                    'kj_blocks_blocks_delete',
                    'blockId',
                    'id_block',
                    Request::METHOD_DELETE
                )
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFilters()
    {
        $filters = (new FilterCollection())
            ->add((new Filter('id_block', TextType::class))
                ->setTypeOptions([
                    'required' => false,
                    'attr' => [
                        'placeholder' => $this->trans('ID', [], 'Admin.Global'),
                    ],
                ])
                ->setAssociatedColumn('id_block')
            )
            ->add((new Filter('name', TextType::class))
                ->setTypeOptions([
                    'required' => false,
                    'attr' => [
                        'placeholder' => $this->trans('Name', [], 'Admin.Global'),
                    ],
                ])
                ->setAssociatedColumn('name')
            )
            ->add((new Filter('type', TextType::class))
                ->setTypeOptions([
                    'required' => false,
                    'attr' => [
                        'placeholder' => $this->trans('Type', [], 'Admin.Global'),
                    ],
                ])
                ->setAssociatedColumn('type')
            )
            ->add((new Filter('group', ChoiceType::class))
                ->setTypeOptions([
                    'required' => false,
                    'choices' => $this->blockGroupBlockChoiceProvider->getChoices(),
                    'choice_translation_domain' => false,
                    'attr' => [
                        'placeholder' => $this->trans('Hook', [], 'Admin.Global'),
                    ],
                ])
                ->setAssociatedColumn('groups')
            )
            ->add((new Filter('actions', SearchAndResetType::class))
                ->setTypeOptions([
                    'reset_route' => 'admin_common_reset_search_by_filter_id',
                    'reset_route_params' => [
                        'filterId' => self::GRID_ID,
                    ],
                    'redirect_route' => 'kj_blocks_blocks_index',
                ])
                ->setAssociatedColumn('actions')
            )
        ;

        if ($this->blockGroupFilter !== null) {
            $positionFilter = (new Filter('position', TextType::class))
                ->setTypeOptions([
                    'required' => false,
                    'attr' => [
                        'placeholder' => $this->trans('Position', [], 'Admin.Global'),
                    ],
                ])
                ->setAssociatedColumn('position')
            ;

            $filters->add($positionFilter);
        }

        return $filters;
    }

    /**
     * @return array<string, mixed>
     */
    private function getRouteOptions(string $route): array
    {
        return [
            'route' => $route,
            'route_param_name' => 'blockId',
            'route_param_field' => 'id_block',
        ];
    }

    private function getBlockGroupFilter(
        AdminFilterRepository $adminFilterRepository,
        int $employeeId,
        int $shopId
    ): ?string {
        $adminFilter = $adminFilterRepository->findByEmployeeAndFilterId(
            $employeeId,
            $shopId,
            self::GRID_ID
        );

        if ($adminFilter === null) {
            $adminFilter = $adminFilterRepository->findByEmployeeAndFilterId(
                $employeeId,
                (new Configuration())->getInt('PS_SHOP_DEFAULT'),
                self::GRID_ID
            );
        }

        if ($adminFilter === null) {
            return null;
        }

        $filter = json_decode($adminFilter->getFilter(), true);

        if (!is_array($filter) || !key_exists('filters', $filter)) {
            return null;
        }

        $filters = $filter['filters'];

        if (!is_array($filters) || !key_exists('group', $filters)) {
            return null;
        }

        return strval($filters['group']);
    }
}
