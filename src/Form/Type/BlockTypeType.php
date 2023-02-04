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

use Kaudaj\Module\Blocks\BlockInterface;
use Kaudaj\Module\Blocks\BlockTypeProvider;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class BlockTypeType extends TranslatorAwareType
{
    public const FIELD_TYPE = 'type';
    public const FIELD_OPTIONS = 'options';

    /**
     * @var BlockTypeProvider<BlockInterface>
     */
    private $blockTypeProvider;

    /**
     * @param array<string, mixed> $locales
     * @param BlockTypeProvider<BlockInterface> $blockTypeProvider
     */
    public function __construct(TranslatorInterface $translator, array $locales, BlockTypeProvider $blockTypeProvider)
    {
        parent::__construct($translator, $locales);

        $this->blockTypeProvider = $blockTypeProvider;
    }

    /**
     * @param FormBuilderInterface<string, mixed> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $blockTypeChoices = [];
        foreach ($this->blockTypeProvider->getBlockTypes() as $moduleBlocks) {
            foreach ($moduleBlocks as $block) {
                $blockTypeChoices[$block->getLocalizedName()] = $block->getName();
            }
        }

        $builder
            ->add(self::FIELD_TYPE, ChoiceType::class, [
                'choices' => $blockTypeChoices,
                'attr' => [
                    'data-toggle' => 'select2',
                    'data-minimumResultsForSearch' => '7',
                ],
                'required' => true,
                'label' => $this->trans('Block type', 'Modules.Kjblocks.Admin'),
            ])
            ->add(self::FIELD_OPTIONS, FormType::class, [
                'label' => false,
            ])
        ;

        $this->addListenersForTypeField($builder);
    }

    /**
     * @param FormBuilderInterface<string, mixed> $builder
     */
    private function addListenersForTypeField(FormBuilderInterface &$builder): void
    {
        $formModifier = function (FormInterface $form, string $blockName): void {
            $fieldOptions = $form->get(self::FIELD_OPTIONS)->getConfig()->getOptions();

            $block = $this->blockTypeProvider->getBlockType($blockName);
            if (!$block) {
                return;
            }

            $form->add(self::FIELD_OPTIONS, $block->getFormType(), $fieldOptions);
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();

                $formModifier(
                    $event->getForm(),
                    is_array($data) && key_exists(self::FIELD_TYPE, $data)
                        ? strval($data[self::FIELD_TYPE])
                        : 'text'
                );
            }
        );

        $builder->get(self::FIELD_TYPE)->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $type = $event->getData();
                $form = $event->getForm()->getParent();

                if (null === $form || !$type) {
                    return;
                }

                $formModifier($form, strval($type));
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('label', false)
        ;
    }
}
