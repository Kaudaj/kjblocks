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

use Kaudaj\Module\Blocks\AbstractBlockTypeProvider;
use Kaudaj\Module\Blocks\Domain\Block\Query\GetBlock;
use Kaudaj\Module\Blocks\Entity\Block;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class BlockTypeType extends TranslatorAwareType
{
    public const FIELD_TYPE = 'type';
    public const FIELD_OPTIONS = 'options';

    /**
     * @var AbstractBlockTypeProvider
     */
    protected $blockTypeProvider;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var CommandBusInterface
     */
    protected $commandBus;

    /**
     * @var MultiShopCheckboxEnabler
     */
    protected $multiShopCheckboxEnabler;

    /**
     * @param array<string, mixed> $locales
     * @param AbstractBlockTypeProvider $blockTypeProvider
     */
    public function __construct(
        TranslatorInterface $translator,
        array $locales,
        AbstractBlockTypeProvider $blockTypeProvider,
        RequestStack $requestStack,
        CommandBusInterface $commandBus,
        MultiShopCheckboxEnabler $multiShopCheckboxEnabler
    ) {
        parent::__construct($translator, $locales);

        $this->blockTypeProvider = $blockTypeProvider;
        $this->requestStack = $requestStack;
        $this->commandBus = $commandBus;
        $this->multiShopCheckboxEnabler = $multiShopCheckboxEnabler;
    }

    /**
     * @param FormBuilderInterface<string, mixed> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addFields($builder);

        $this->addListenersForTypeField($builder);
    }

    /**
     * @param FormBuilderInterface<string, mixed> $builder
     */
    private function addFields(FormBuilderInterface &$builder): void
    {
        $builder
            ->add(self::FIELD_TYPE, BlockTypePicker::class, [
                'required' => true,
                'label' => $this->trans('Type', 'Admin.Global'),
                'block_types' => $this->blockTypeProvider->getBlockTypes(),
            ])
            ->add(self::FIELD_OPTIONS, FormType::class, [
                'label' => false,
            ])
        ;
    }

    /**
     * @param FormBuilderInterface<string, mixed> $builder
     */
    private function addListenersForTypeField(FormBuilderInterface &$builder): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $data = $event->getData();

                $this->updateOptionsField(
                    $event->getForm(),
                    is_array($data) && key_exists(self::FIELD_TYPE, $data)
                        ? (string) $data[self::FIELD_TYPE]
                        : 'text'
                );
            }
        );

        $builder->get(self::FIELD_TYPE)->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $type = $event->getData();
                $form = $event->getForm()->getParent();

                if (null === $form || !$type) {
                    return;
                }

                $this->updateOptionsField($form, (string) $type);
            }
        );
    }

    /**
     * @param FormInterface<string, mixed> $form
     */
    private function updateOptionsField(FormInterface $form, string $blockName): void
    {
        $blockType = $this->blockTypeProvider->getBlockType($blockName);

        $form->add(
            self::FIELD_OPTIONS,
            $blockType->getFormType(),
            $form->get(self::FIELD_OPTIONS)->getConfig()->getOptions()
        );

        $this->makeFormMultistore($form);
    }

    /**
     * @param FormInterface<string, mixed> $form
     */
    protected function makeFormMultistore(FormInterface $form): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }

        $blockId = $request->attributes->getInt('blockId');
        if (!$blockId) {
            return;
        }

        /** @var Block */
        $block = $this->commandBus->handle(new GetBlock($blockId));

        $this->multiShopCheckboxEnabler->makeFormMultistore(
            $form->get(self::FIELD_OPTIONS),
            function ($fieldName, $shopId, $shopGroupId) use ($block) {
                return call_user_func([$this, 'isOptionOverridenForShop'], $block, $fieldName, $shopId, $shopGroupId);
            }
        );
    }

    public function isOptionOverridenForShop(Block $block, string $option, ?int $shopGroupId = null, ?int $shopId = null): bool
    {
        $blockShop = $block->getBlockShop($shopId, $shopGroupId);

        $blockOptions = $blockShop ? json_decode($blockShop->getOptions() ?: '', true) : [];
        $blockOptions = is_array($blockOptions) ? $blockOptions : [];

        return key_exists($option, $blockOptions);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'label' => false,
            ])
        ;
    }
}
