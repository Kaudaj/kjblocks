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

namespace Kaudaj\Module\ContentBlocks\Controller\Admin;

use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Command\DeleteContentBlockCommand;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Exception\CannotAddContentBlockException;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Exception\CannotDeleteContentBlockException;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Exception\CannotUpdateContentBlockException;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Exception\ContentBlockException;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Exception\ContentBlockNotFoundException;
use Kaudaj\Module\ContentBlocks\Grid\Definition\Factory\ContentBlockGridDefinitionFactory;
use Kaudaj\Module\ContentBlocks\Search\Filters\ContentBlockFilters;
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

class ContentBlockController extends FrameworkBundleAdminController
{
    /**
     * Display content block grid
     *
     * @AdminSecurity("is_granted(['read'], request.get('_legacy_controller'))")
     */
    public function indexAction(Request $request, ContentBlockFilters $filters): Response
    {
        /** @var GridFactoryInterface */
        $contentBlocksGridFactory = $this->get('kaudaj.module.content_blocks.grid.factory.content_block');

        $contentBlocksGrid = $contentBlocksGridFactory->getGrid($filters);

        return $this->render('@Modules/kjcontentblocks/views/templates/back/layouts/content-block/index.html.twig', [
            'contentBlocksGrid' => $this->presentGrid($contentBlocksGrid),
            'layoutHeaderToolbarBtn' => [
                'add' => [
                    'href' => $this->generateUrl('kj_content_blocks_content_blocks_create'),
                    'desc' => $this->trans('Add new content block', 'Modules.Mvconfigurator.Admin'),
                    'icon' => 'add_circle_outline',
                ],
            ],
        ]);
    }

    /**
     * Show content block create form & handle processing of it
     *
     * @AdminSecurity("is_granted(['create'], request.get('_legacy_controller'))")
     */
    public function createAction(Request $request): Response
    {
        /** @var FormBuilderInterface */
        $contentBlockFormBuilder = $this->get('kaudaj.module.content_blocks.form.builder.content_block');
        $contentBlockForm = $contentBlockFormBuilder->getForm();

        $contentBlockForm->handleRequest($request);

        /** @var FormHandlerInterface */
        $contentBlockFormHandler = $this->get('kaudaj.module.content_blocks.form.handler.content_block');
        $result = $contentBlockFormHandler->handle($contentBlockForm);

        if (null !== $result->getIdentifiableObjectId()) {
            $this->addFlash('success', $this->trans('Successful creation.', 'Admin.Notifications.Success'));

            return $this->redirectToIndexRoute();
        }

        return $this->render('@Modules/kjcontentblocks/views/templates/back/layouts/content-block/create.html.twig', [
            'contentBlockForm' => $contentBlockForm->createView(),
        ]);
    }

    /**
     * Show content block edit form & handle processing of it
     *
     * @AdminSecurity("is_granted(['update'], request.get('_legacy_controller'))")
     */
    public function editAction(int $contentBlockId, Request $request): Response
    {
        /** @var FormBuilderInterface */
        $contentBlockFormBuilder = $this->get('kaudaj.module.content_blocks.form.builder.content_block');
        $contentBlockForm = $contentBlockFormBuilder->getFormFor($contentBlockId);

        $contentBlockForm->handleRequest($request);

        /** @var FormHandlerInterface */
        $contentBlockFormHandler = $this->get('kaudaj.module.content_blocks.form.handler.content_block');
        $result = $contentBlockFormHandler->handleFor($contentBlockId, $contentBlockForm);

        if (null !== $result->getIdentifiableObjectId()) {
            $this->addFlash('success', $this->trans('Successful creation.', 'Admin.Notifications.Success'));

            return $this->redirectToIndexRoute();
        }

        return $this->render('@Modules/kjcontentblocks/views/templates/back/layouts/content-block/create.html.twig', [
            'contentBlockForm' => $contentBlockForm->createView(),
        ]);
    }

    /**
     * Deletes content block
     *
     * @AdminSecurity("is_granted(['delete'], request.get('_legacy_controller'))")
     */
    public function deleteAction(Request $request, int $contentBlockId): Response
    {
        try {
            $this->getCommandBus()->handle(new DeleteContentBlockCommand($contentBlockId));

            $this->addFlash('success', $this->trans('Successful deletion.', 'Admin.Notifications.Success'));
        } catch (ContentBlockException $e) {
            $this->addFlash('error', $this->getErrorMessageForException($e, $this->getErrorMessages()));
        }

        return $this->redirectToIndexRoute();
    }

    /**
     * Updates content blocks positions
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
                    'Modules.Kjcontentblocks.Admin'
                ),
            ]);

            return $redirectResponse;
        }

        $positionsData = [
            'positions' => $request->request->get('positions'),
            'parentId' => $hookId,
        ];

        /** @var PositionDefinition */
        $positionDefinition = $this->get('kaudaj.module.content_blocks.grid.position_definition.content_block');

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
        $updater = $this->get('prestashop.core.grid.position.doctrine_grid_position_updater');

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
            ContentBlockGridDefinitionFactory::GRID_ID
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
        return $this->redirectToRoute('kj_content_blocks_content_blocks_index');
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    private function getErrorMessages(): array
    {
        return [
            ContentBlockNotFoundException::class => $this->trans(
                'The object cannot be loaded (or found)',
                'Admin.Notifications.Error'
            ),
            CannotAddContentBlockException::class => $this->trans(
                'An error occurred while attempting to save.',
                'Admin.Notifications.Error'
            ),
            CannotUpdateContentBlockException::class => $this->trans(
                'An error occurred while attempting to save.',
                'Admin.Notifications.Error'
            ),
            CannotDeleteContentBlockException::class => $this->trans(
                'An error occurred while deleting the object.',
                'Admin.Notifications.Error'
            ),
        ];
    }
}
