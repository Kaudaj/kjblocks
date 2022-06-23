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

use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\Builder\FormBuilderInterface;
use PrestaShop\PrestaShop\Core\Form\IdentifiableObject\Handler\FormHandlerInterface;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentBlockController extends FrameworkBundleAdminController
{
    /**
     * @AdminSecurity(
     *     "is_granted('read', request.get('_legacy_controller'))",
     *     redirectRoute="admin_module_manage",
     *     message="You need permission to read this."
     * )
     */
    public function indexAction(): Response
    {
        return new Response('index');
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

            return $this->redirectToRoute('kj_content_blocks_content_blocks_index');
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

            return $this->redirectToRoute('kj_content_blocks_content_blocks_index');
        }

        return $this->render('@Modules/kjcontentblocks/views/templates/back/layouts/content-block/create.html.twig', [
            'contentBlockForm' => $contentBlockForm->createView(),
        ]);
    }
}
