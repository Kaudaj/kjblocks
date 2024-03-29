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

final class BlockGroupGridDefinitionFactory extends AbstractGridDefinitionFactory
{
    use DeleteActionTrait;

    public const GRID_ID = 'block_groups';

    /**
     * @var FormChoiceProviderInterface
     */
    private $hookChoiceProvider;

    /**
     * @var string|null
     */
    private $hookFilter;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        FormChoiceProviderInterface $hookChoiceProvider,
        AdminFilterRepository $adminFilterRepository,
        ContextEmployeeProvider $contextEmployeeProvider,
        ShopContext $shopContext
    ) {
        parent::__construct($hookDispatcher);

        $this->hookChoiceProvider = $hookChoiceProvider;

        $this->hookFilter = $this->getHookFilter(
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
        return $this->trans('Block groups', [], 'Modules.Kjblocks.Admin');
    }

    protected function getColumns()
    {
        $columns = (new ColumnCollection())
            ->add((new DataColumn('id_block_group'))
                ->setName($this->trans('ID', [], 'Admin.Global'))
                ->setOptions([
                    'field' => 'id_block_group',
                ])
            )
            ->add((new DataColumn('name'))
                ->setName($this->trans('Name', [], 'Admin.Global'))
                ->setOptions([
                    'field' => 'name',
                ])
            )
            ->add((new DataColumn('hooks'))
                ->setName(
                    $this->hookFilter === null
                        ? $this->trans('Hooks', [], 'Modules.Kjblocks.Admin')
                        : $this->trans('Hook', [], 'Admin.Global')
                )
                ->setOptions([
                    'field' => 'hooks',
                ])
            )
            ->add((new ActionColumn('actions'))
                ->setName($this->trans('Actions', [], 'Admin.Global'))
                ->setOptions([
                    'actions' => $this->getRowActions(),
                ])
            )
        ;

        if ($this->hookFilter !== null) {
            $positionColumn = (new PositionColumn('position'))
                ->setName($this->trans('Position', [], 'Modules.Kjblocks.Admin'))
                ->setOptions([
                    'id_field' => 'id_block_group',
                    'position_field' => 'position',
                    'update_method' => 'POST',
                    'update_route' => 'kj_blocks_block_groups_update_position',
                ])
            ;

            $columns->addAfter('hooks', $positionColumn);
        }

        return $columns;
    }

    private function getRowActions(): RowActionCollectionInterface
    {
        return (new RowActionCollection())
            ->add((new LinkRowAction('view'))
                ->setName($this->trans('View', [], 'Admin.Actions'))
                ->setIcon('zoom_in')
                ->setOptions($this->getRouteOptions('kj_blocks_block_groups_view') + [
                    'clickable_row' => true,
                ])
            )
            ->add((new LinkRowAction('edit'))
                ->setName($this->trans('Edit', [], 'Admin.Actions'))
                ->setIcon('edit')
                ->setOptions($this->getRouteOptions('kj_blocks_block_groups_edit'))
            )
            ->add(
                $this->buildDeleteAction(
                    'kj_blocks_block_groups_delete',
                    'blockGroupId',
                    'id_block_group',
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
            ->add((new Filter('id_block_group', TextType::class))
                ->setTypeOptions([
                    'required' => false,
                    'attr' => [
                        'placeholder' => $this->trans('ID', [], 'Admin.Global'),
                    ],
                ])
                ->setAssociatedColumn('id_block_group')
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
            ->add((new Filter('hook', ChoiceType::class))
                ->setTypeOptions([
                    'required' => false,
                    'choices' => $this->hookChoiceProvider->getChoices(),
                    'choice_translation_domain' => false,
                    'attr' => [
                        'placeholder' => $this->trans('Hook', [], 'Admin.Global'),
                    ],
                ])
                ->setAssociatedColumn('hooks')
            )
            ->add((new Filter('actions', SearchAndResetType::class))
                ->setTypeOptions([
                    'reset_route' => 'admin_common_reset_search_by_filter_id',
                    'reset_route_params' => [
                        'filterId' => self::GRID_ID,
                    ],
                    'redirect_route' => 'kj_blocks_block_groups_index',
                ])
                ->setAssociatedColumn('actions')
            )
        ;

        if ($this->hookFilter !== null) {
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
            'route_param_name' => 'blockGroupId',
            'route_param_field' => 'id_block_group',
        ];
    }

    private function getHookFilter(
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

        if (!is_array($filters) || !key_exists('hook', $filters)) {
            return null;
        }

        return (string) $filters['hook'];
    }
}
