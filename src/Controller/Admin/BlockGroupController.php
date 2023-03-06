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

namespace Kaudaj\Module\Blocks\Controller\Admin;

use Kaudaj\Module\Blocks\Domain\Block\Exception\BlockException;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Command\DeleteBlockGroupCommand;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Exception\BlockGroupNotFoundException;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Exception\CannotAddBlockGroupException;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Exception\CannotDeleteBlockGroupException;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Exception\CannotUpdateBlockGroupException;
use Kaudaj\Module\Blocks\Grid\Definition\Factory\BlockGridDefinitionFactory;
use Kaudaj\Module\Blocks\Grid\Definition\Factory\BlockGroupGridDefinitionFactory;
use Kaudaj\Module\Blocks\Search\Filters\BlockGroupFilters;
use PrestaShop\PrestaShop\Adapter\Shop\Context as ShopContext;
use PrestaShop\PrestaShop\Core\Employee\ContextEmployeeProviderInterface;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\Builder\FormBuilderInterface;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\Handler\FormHandlerInterface;
use PrestaShop\PrestaShop\Core\Grid\GridFactoryInterface;
use PrestaShop\PrestaShop\Core\Grid\Position\Exception\PositionDataException;
use PrestaShop\PrestaShop\Core\Grid\Position\Exception\PositionUpdateException;
use PrestaShop\PrestaShop\Core\Grid\Position\GridPositionUpdaterInterface;
use PrestaShop\PrestaShop\Core\Grid\Position\PositionDefinition;
use PrestaShop\PrestaShop\Core\Grid\Position\PositionUpdateFactoryInterface;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Entity\Repository\AdminFilterRepository;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockGroupController extends FrameworkBundleAdminController
{
    /**
     * @AdminSecurity("is_granted(['read'], request.get('_legacy_controller'))")
     */
    public function indexAction(Request $request, BlockGroupFilters $filters): Response
    {
        /** @var GridFactoryInterface */
        $blockGroupsGridFactory = $this->get('kaudaj.module.blocks.grid.factory.block_group');

        $blockGroupsGrid = $blockGroupsGridFactory->getGrid($filters);

        return $this->render('@Modules/kjblocks/views/templates/back/layouts/block-group/index.html.twig', [
            'blockGroupsGrid' => $this->presentGrid($blockGroupsGrid),
            'layoutHeaderToolbarBtn' => [
                'add' => [
                    'href' => $this->generateUrl('kj_blocks_block_groups_create'),
                    'desc' => $this->trans('Add new block group', 'Modules.Kjblocks.Admin'),
                    'icon' => 'add_circle_outline',
                ],
            ],
        ]);
    }

    /**
     * @AdminSecurity(
     *     "is_granted('read', 'KJBlocksBlock')",
     *     redirectRoute="kj_blocks_block_groups_index",
     * )
     */
    public function viewAction(Request $request, int $blockGroupId): Response
    {
        return $this->redirectToRoute('kj_blocks_blocks_index', [
            BlockGridDefinitionFactory::GRID_ID => [
                'filters' => [
                    'group' => $blockGroupId,
                ],
            ],
        ]);
    }

    /**
     * @AdminSecurity("is_granted(['create'], request.get('_legacy_controller'))")
     */
    public function createAction(Request $request): Response
    {
        /** @var FormBuilderInterface */
        $blockGroupFormBuilder = $this->get('kaudaj.module.blocks.form.builder.block_group');
        $blockGroupForm = $blockGroupFormBuilder->getForm();

        $blockGroupForm->handleRequest($request);

        /** @var FormHandlerInterface */
        $blockGroupFormHandler = $this->get('kaudaj.module.blocks.form.handler.block_group');
        $result = $blockGroupFormHandler->handle($blockGroupForm);

        if (null !== $result->getIdentifiableObjectId()) {
            $this->addFlash('success', $this->trans('Successful creation.', 'Admin.Notifications.Success'));

            return $this->redirectToIndexRoute();
        }

        return $this->render('@Modules/kjblocks/views/templates/back/layouts/block-group/create.html.twig', [
            'blockGroupForm' => $blockGroupForm->createView(),
        ]);
    }

    /**
     * @AdminSecurity("is_granted(['update'], request.get('_legacy_controller'))")
     */
    public function editAction(int $blockGroupId, Request $request): Response
    {
        /** @var FormBuilderInterface */
        $blockGroupFormBuilder = $this->get('kaudaj.module.blocks.form.builder.block_group');
        $blockGroupForm = $blockGroupFormBuilder->getFormFor($blockGroupId);

        $blockGroupForm->handleRequest($request);

        /** @var FormHandlerInterface */
        $blockGroupFormHandler = $this->get('kaudaj.module.blocks.form.handler.block_group');
        $result = $blockGroupFormHandler->handleFor($blockGroupId, $blockGroupForm);

        if (null !== $result->getIdentifiableObjectId()) {
            $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));

            return $this->redirectToIndexRoute();
        }

        return $this->render('@Modules/kjblocks/views/templates/back/layouts/block-group/edit.html.twig', [
            'blockGroupForm' => $blockGroupForm->createView(),
        ]);
    }

    /**
     * @AdminSecurity("is_granted(['delete'], request.get('_legacy_controller'))")
     */
    public function deleteAction(Request $request, int $blockGroupId): Response
    {
        try {
            $this->getCommandBus()->handle(new DeleteBlockGroupCommand($blockGroupId));

            $this->addFlash('success', $this->trans('Successful deletion.', 'Admin.Notifications.Success'));
        } catch (BlockException $e) {
            $this->addFlash('error', $this->getErrorMessageForException($e, $this->getErrorMessages()));
        }

        return $this->redirectToIndexRoute();
    }

    /**
     * @AdminSecurity("is_granted(['delete'], request.get('_legacy_controller'))")
     */
    public function updatePositionAction(Request $request): Response
    {
        $redirectResponse = $this->redirectToIndexRoute();

        $hookId = $this->getHookIdFromFilters();

        if (!$hookId) {
            $this->flashErrors([
                $this->trans(
                    "Can't update positions if hook id filter is not set.",
                    'Modules.KjblockGroups.Admin'
                ),
            ]);

            return $redirectResponse;
        }

        $positionsData = [
            'positions' => $request->request->get('positions'),
            'parentId' => $hookId,
        ];

        /** @var PositionDefinition */
        $positionDefinition = $this->get('kaudaj.module.blocks.grid.position_definition.block_group');

        /** @var PositionUpdateFactoryInterface */
        $positionUpdateFactory = $this->get('prestashop.core.grid.position.position_update_factory');

        try {
            $positionUpdate = $positionUpdateFactory->buildPositionUpdate($positionsData, $positionDefinition);
        } catch (PositionDataException $e) {
            $errors = [$e->toArray()];
            $this->flashErrors($errors);

            return $redirectResponse;
        }

        /** @var GridPositionUpdaterInterface */
        $updater = $this->get('kaudaj.module.blocks.grid.position.block_grid_position_updater');

        try {
            $updater->update($positionUpdate);

            $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));
        } catch (PositionUpdateException $e) {
            $errors = [$e->toArray()];
            $this->flashErrors($errors);
        }

        return $redirectResponse;
    }

    private function getHookIdFromFilters(): ?int
    {
        /** @var AdminFilterRepository */
        $adminFilterRepository = $this->get('prestashop.core.admin.admin_filter.repository');

        /** @var ContextEmployeeProviderInterface */
        $contextEmployeeProvider = $this->get('prestashop.adapter.data_provider.employee');

        /** @var ShopContext */
        $shopContext = $this->get('prestashop.adapter.shop.context');

        $adminFilter = $adminFilterRepository->findByEmployeeAndFilterId(
            $contextEmployeeProvider->getId(),
            $shopContext->getContextShopID(),
            BlockGroupGridDefinitionFactory::GRID_ID
        );

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

        return intval($filters['hook']);
    }

    private function redirectToIndexRoute(): Response
    {
        return $this->redirectToRoute('kj_blocks_block_groups_index');
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    private function getErrorMessages(): array
    {
        return [
            BlockGroupNotFoundException::class => $this->trans(
                'The object cannot be loaded (or found)',
                'Admin.Notifications.Error'
            ),
            CannotAddBlockGroupException::class => $this->trans(
                'An error occurred while attempting to save.',
                'Admin.Notifications.Error'
            ),
            CannotUpdateBlockGroupException::class => $this->trans(
                'An error occurred while attempting to save.',
                'Admin.Notifications.Error'
            ),
            CannotDeleteBlockGroupException::class => $this->trans(
                'An error occurred while deleting the object.',
                'Admin.Notifications.Error'
            ),
        ];
    }
}
