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

namespace Kaudaj\Module\Blocks\Form\Type\Block;

use Kaudaj\Module\Blocks\Constraint\TypedRegex;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Image;

class ContainerBlockType extends TranslatorAwareType
{
    public const FIELD_WIDTH = 'width';
    public const FIELD_HEIGHT = 'height';
    public const FIELD_CLASSES = 'classes';
    public const FIELD_IDENTIFIER = 'identifier';
    public const FIELD_BACKGROUND_IMAGE = 'background_image';

    /**
     * @param FormBuilderInterface<string, mixed> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(self::FIELD_WIDTH, IntegerType::class, [
                'label' => $this->trans('Width', 'Admin.Global'),
                'help' => $this->trans('Width of the block.', 'Modules.Kjblocks.Admin'),
                'required' => false,
                'constraints' => [
                    new GreaterThanOrEqual(0),
                ],
            ])
            ->add(self::FIELD_HEIGHT, IntegerType::class, [
                'label' => $this->trans('Height', 'Admin.Global'),
                'help' => $this->trans('Height of the block.', 'Modules.Kjblocks.Admin'),
                'required' => false,
                'constraints' => [
                    new GreaterThanOrEqual(0),
                ],
            ])
            ->add(self::FIELD_CLASSES, TextType::class, [
                'label' => $this->trans('Classes', 'Admin.Global'),
                'help' => $this->trans('Classes of the block, separated by a comma.', 'Modules.Kjblocks.Admin'),
                'required' => false,
                'constraints' => [
                    new TypedRegex(TypedRegex::TYPE_SELECTORS),
                ],
            ])
            ->add(self::FIELD_IDENTIFIER, TextType::class, [
                'label' => $this->trans('Identifier', 'Admin.Global'),
                'help' => $this->trans('Id of the block.', 'Modules.Kjblocks.Admin'),
                'required' => false,
                'constraints' => [
                    new TypedRegex(TypedRegex::TYPE_SELECTOR),
                ],
            ])
            ->add(self::FIELD_BACKGROUND_IMAGE, FileType::class, [
                'label' => $this->trans('Background image', 'Admin.Global'),
                'help' => $this->trans('Background image of the block.', 'Modules.Kjblocks.Admin'),
                'required' => false,
                'constraints' => [
                    new Image(),
                ],
            ])
        ;
    }
}
