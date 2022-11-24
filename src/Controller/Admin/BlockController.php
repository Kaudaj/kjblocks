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

use Kaudaj\Module\Blocks\BlockTypeProvider;
use Kaudaj\Module\Blocks\Domain\Block\Command\DeleteBlockCommand;
use Kaudaj\Module\Blocks\Domain\Block\Exception\BlockException;
use Kaudaj\Module\Blocks\Domain\Block\Exception\BlockNotFoundException;
use Kaudaj\Module\Blocks\Domain\Block\Exception\CannotAddBlockException;
use Kaudaj\Module\Blocks\Domain\Block\Exception\CannotDeleteBlockException;
use Kaudaj\Module\Blocks\Domain\Block\Exception\CannotUpdateBlockException;
use Kaudaj\Module\Blocks\Grid\Definition\Factory\BlockGridDefinitionFactory;
use Kaudaj\Module\Blocks\Search\Filters\BlockFilters;
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

class BlockController extends FrameworkBundleAdminController
{
    /**
     * Display block grid
     *
     * @AdminSecurity("is_granted(['read'], request.get('_legacy_controller'))")
     */
    public function indexAction(Request $request, BlockFilters $filters): Response
    {
        /** @var GridFactoryInterface */
        $blocksGridFactory = $this->get('kaudaj.module.blocks.grid.factory.block');

        $blocksGrid = $blocksGridFactory->getGrid($filters);

        return $this->render('@Modules/kjblocks/views/templates/back/layouts/block/index.html.twig', [
            'blocksGrid' => $this->presentGrid($blocksGrid),
            'layoutHeaderToolbarBtn' => [
                'add' => [
                    'href' => $this->generateUrl('kj_blocks_blocks_create'),
                    'desc' => $this->trans('Add new block', 'Modules.Mvconfigurator.Admin'),
                    'icon' => 'add_circle_outline',
                ],
            ],
        ]);
    }

    /**
     * Show block create form & handle processing of it
     *
     * @AdminSecurity("is_granted(['create'], request.get('_legacy_controller'))")
     */
    public function createAction(Request $request): Response
    {
        /** @var FormBuilderInterface */
        $blockFormBuilder = $this->get('kaudaj.module.blocks.form.builder.block');
        $blockForm = $blockFormBuilder->getForm();

        $blockForm->handleRequest($request);

        /** @var FormHandlerInterface */
        $blockFormHandler = $this->get('kaudaj.module.blocks.form.handler.block');
        $result = $blockFormHandler->handle($blockForm);

        if (null !== $result->getIdentifiableObjectId()) {
            $this->addFlash('success', $this->trans('Successful creation.', 'Admin.Notifications.Success'));

            return $this->redirectToIndexRoute();
        }

        return $this->render('@Modules/kjblocks/views/templates/back/layouts/block/create.html.twig', [
            'blockForm' => $blockForm->createView(),
            'expansionsFormThemes' => $this->getExpansionsFormThemes(),
        ]);
    }

    /**
     * Show block edit form & handle processing of it
     *
     * @AdminSecurity("is_granted(['update'], request.get('_legacy_controller'))")
     */
    public function editAction(int $blockId, Request $request): Response
    {
        /** @var FormBuilderInterface */
        $blockFormBuilder = $this->get('kaudaj.module.blocks.form.builder.block');
        $blockForm = $blockFormBuilder->getFormFor($blockId);

        $blockForm->handleRequest($request);

        /** @var FormHandlerInterface */
        $blockFormHandler = $this->get('kaudaj.module.blocks.form.handler.block');
        $result = $blockFormHandler->handleFor($blockId, $blockForm);

        if (null !== $result->getIdentifiableObjectId()) {
            $this->addFlash('success', $this->trans('Successful creation.', 'Admin.Notifications.Success'));

            return $this->redirectToIndexRoute();
        }

        return $this->render('@Modules/kjblocks/views/templates/back/layouts/block/edit.html.twig', [
            'blockForm' => $blockForm->createView(),
            'expansionsFormThemes' => $this->getExpansionsFormThemes(),
        ]);
    }

    /**
     * @return string[]
     */
    private function getExpansionsFormThemes(): array
    {
        $formThemes = [];

        foreach (array_keys(BlockTypeProvider::getBlockTypes()) as $moduleName) {
            $filename = _PS_MODULE_DIR_ . "$moduleName/views/templates/form_theme.html.twig";

            if (file_exists($filename)) {
                $formThemes[] = str_replace(_PS_MODULE_DIR_, '@Modules/', $filename);
            }
        }

        return $formThemes;
    }

    /**
     * Deletes block
     *
     * @AdminSecurity("is_granted(['delete'], request.get('_legacy_controller'))")
     */
    public function deleteAction(Request $request, int $blockId): Response
    {
        try {
            $this->getCommandBus()->handle(new DeleteBlockCommand($blockId));

            $this->addFlash('success', $this->trans('Successful deletion.', 'Admin.Notifications.Success'));
        } catch (BlockException $e) {
            $this->addFlash('error', $this->getErrorMessageForException($e, $this->getErrorMessages()));
        }

        return $this->redirectToIndexRoute();
    }

    /**
     * Updates blocks positions
     *
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
                    'Modules.Kjblocks.Admin'
                ),
            ]);

            return $redirectResponse;
        }

        $positionsData = [
            'positions' => $request->request->get('positions'),
            'parentId' => $hookId,
        ];

        /** @var PositionDefinition */
        $positionDefinition = $this->get('kaudaj.module.blocks.grid.position_definition.block');

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
            BlockGridDefinitionFactory::GRID_ID
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
        return $this->redirectToRoute('kj_blocks_blocks_index');
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    private function getErrorMessages(): array
    {
        return [
            BlockNotFoundException::class => $this->trans(
                'The object cannot be loaded (or found)',
                'Admin.Notifications.Error'
            ),
            CannotAddBlockException::class => $this->trans(
                'An error occurred while attempting to save.',
                'Admin.Notifications.Error'
            ),
            CannotUpdateBlockException::class => $this->trans(
                'An error occurred while attempting to save.',
                'Admin.Notifications.Error'
            ),
            CannotDeleteBlockException::class => $this->trans(
                'An error occurred while deleting the object.',
                'Admin.Notifications.Error'
            ),
        ];
    }
}
