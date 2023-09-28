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

namespace Kaudaj\Module\Blocks\Form\Type;

use Doctrine\ORM\EntityManager;
use PrestaShop\PrestaShop\Adapter\Shop\Context as ShopContext;
use PrestaShop\PrestaShop\Core\Feature\FeatureInterface;
use PrestaShopBundle\Entity\Shop;
use PrestaShopBundle\Entity\ShopGroup;
use PrestaShopBundle\Service\Form\MultistoreCheckboxEnabler;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormInterface;
use Twig;

class MultiShopCheckboxEnabler
{
    /**
     * @var FeatureInterface
     */
    private $multistoreFeature;

    /**
     * @var ShopContext
     */
    private $shopContext;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Twig\Environment
     */
    private $twig;

    public function __construct(
        FeatureInterface $multistoreFeature,
        ShopContext $shopContext,
        EntityManager $entityManager,
        Twig\Environment $twig
    ) {
        $this->multistoreFeature = $multistoreFeature;
        $this->shopContext = $shopContext;
        $this->entityManager = $entityManager;
        $this->twig = $twig;
    }

    /**
     * @param FormInterface<string, mixed> $form
     */
    public function makeFormMultistore(FormInterface $form, callable $isOverridenForShop): void
    {
        if (!$this->multistoreFeature->isUsed()) {
            return;
        }

        $shopConstraint = $this->shopContext->getShopConstraint(true);

        $contextShopId = $shopConstraint->getShopId() ? $shopConstraint->getShopId()->getValue() : null;
        $contextShopGroupId = $shopConstraint->getShopGroupId() ? $shopConstraint->getShopGroupId()->getValue() : null;

        foreach ($form->all() as $child) {
            $childOptions = $child->getConfig()->getOptions();
            $childName = $child->getName();

            $isOverriddenInCurrentContext = call_user_func($isOverridenForShop, $childName, $contextShopGroupId, $contextShopId);

            $childOptions['attr']['disabled'] = !$this->shopContext->isAllShopContext() && !$isOverriddenInCurrentContext;

            // add multistore dropdown in field option
            if (!$this->shopContext->isShopContext()) {
                $childOptions['multistore_dropdown'] = $this->renderDropdown($childName, $isOverridenForShop);
            }

            $childType = get_class($child->getConfig()->getType()->getInnerType());
            $form->add($childName, $childType, $childOptions);

            // for each field in the configuration form, we add a multistore checkbox (except in all shop context)
            if (!$this->shopContext->isAllShopContext()) {
                $fieldName = MultistoreCheckboxEnabler::MULTISTORE_FIELD_PREFIX . $childName;

                $form->add($fieldName, CheckboxType::class, [
                    'required' => false,
                    'data' => $isOverriddenInCurrentContext,
                    'label' => false,
                    'attr' => [
                        'material_design' => true,
                        'class' => 'multistore-checkbox',
                    ],
                ]);
            }
        }
    }

    private function renderDropdown(string $fieldName, callable $isOverridenForShop): string
    {
        $groupList = [];

        /** @var ShopGroup[] */
        $shopGroups = $this->entityManager->getRepository(ShopGroup::class)->findBy(['active' => true]);

        foreach ($shopGroups as $group) {
            $contextGroupId = $this->shopContext->getContextShopGroup()->id;
            if (count($group->getShops()) > 0
                && (
                    $this->shopContext->isAllShopContext()
                    || ($this->shopContext->isGroupShopContext() && $group->getId() === $contextGroupId)
                )
            ) {
                $groupList[] = $group;
            }

            /** @var Shop[] */
            $shops = $group->getShops();

            foreach ($shops as $shop) {
                $shopId = $shop->getId();

                $shopOverrides[$shopId] = call_user_func($isOverridenForShop, $fieldName, null, $shopId);
            }
        }

        $dropdownData['isGroupShopContext'] = $this->shopContext->isAllShopContext();

        if (empty($shopOverrides)) {
            return '';
        }

        return $this->twig->render('@Modules/kjblocks/views/templates/back/components/atoms/dropdown.html.twig', [
            'groupList' => $groupList,
            'shopOverrides' => $shopOverrides,
        ]);
    }
}
