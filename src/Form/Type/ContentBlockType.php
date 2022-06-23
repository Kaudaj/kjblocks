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

namespace Kaudaj\Module\ContentBlocks\Form\Type;

use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\CleanHtml;
use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\DefaultLanguage;
use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\TypedRegex;
use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContentBlockType extends TranslatorAwareType
{
    public const FIELD_HOOK = 'hook';
    public const FIELD_NAME = 'name';
    public const FIELD_CONTENT = 'content';

    /**
     * @param FormBuilderInterface<string, mixed> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(self::FIELD_HOOK, TextType::class, [
                'label' => $this->trans('Hook name', 'Modules.Mvconfigurator.Admin'),
                'help' => $this->trans('The hook where the content block will be displayed.', 'Modules.Mvconfigurator.Admin'),
            ])
            ->add(self::FIELD_NAME, TranslatableType::class, [
                'label' => $this->trans('Block name', 'Admin.Global'),
                'help' => $this->trans('Name of the block.', 'Modules.Mvconfigurator.Admin'),
                'constraints' => [
                    new DefaultLanguage(),
                ],
                'options' => [
                    'constraints' => [
                        new NotBlank(),
                        new TypedRegex(TypedRegex::TYPE_GENERIC_NAME),
                    ],
                ],
            ])
            ->add(self::FIELD_CONTENT, TranslatableType::class, [
                'label' => $this->trans('Block content', 'Admin.Global'),
                'help' => $this->trans('Content of the block.', 'Modules.Mvconfigurator.Admin'),
                'constraints' => [
                    new DefaultLanguage(),
                ],
                'options' => [
                    'constraints' => [
                        new NotBlank(),
                        new CleanHtml(),
                    ],
                ],
            ])
        ;
    }
}
