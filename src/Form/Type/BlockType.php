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

use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\DefaultLanguage;
use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\TypedRegex;
use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class BlockType extends TranslatorAwareType
{
    public const FIELD_GROUPS = 'groups';
    public const FIELD_NAME = 'name';
    public const FIELD_TYPE = 'type';
    public const FIELD_OPTIONS = 'options';

    /**
     * @var array<string, int>
     */
    private $groupsChoices;

    /**
     * @var array<string, int>
     */
    private $hookChoices;

    /**
     * @param array<string, mixed> $locales
     * @param array<string, int> $groupsChoices
     * @param array<string, int> $hookChoices
     */
    public function __construct(TranslatorInterface $translator, array $locales, array $groupsChoices, array $hookChoices)
    {
        parent::__construct($translator, $locales);

        $this->groupsChoices = $groupsChoices;
        $this->hookChoices = $hookChoices;
    }

    /**
     * @param FormBuilderInterface<string, mixed> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $groupsChoices = [];
        foreach ($this->groupsChoices as $name => $id) {
            $groupsChoices[$name] = "group-$id";
        }

        $hookChoices = [];
        foreach ($this->hookChoices as $name => $id) {
            $hookChoices[$name] = "hook-$id";
        }

        $builder
            ->add(self::FIELD_GROUPS, ChoiceType::class, [
                'label' => $this->trans('Groups', 'Admin.Global'),
                'help' => $this->trans('Hooks or block groups to which this block belongs', 'Modules.Kjblocks.Admin'),
                'choices' => [
                    $this->trans('Available block groups', 'Modules.Kjblocks.Admin') => array_diff_key($groupsChoices, $hookChoices),
                    $this->trans('Available hooks', 'Modules.Kjblocks.Admin') => $hookChoices,
                ],
                'attr' => [
                    'data-toggle' => 'select2',
                    'data-minimumResultsForSearch' => '7',
                ],
                'multiple' => true,
                'required' => false,
            ])
            ->add(self::FIELD_NAME, TranslatableType::class, [
                'label' => $this->trans('Name', 'Admin.Global'),
                'help' => $this->trans('Name of the block, only visible in the admin panel for internal usage', 'Modules.Kjblocks.Admin'),
                'constraints' => [
                    new DefaultLanguage(),
                ],
                'options' => [
                    'constraints' => [
                        new TypedRegex(TypedRegex::TYPE_GENERIC_NAME),
                    ],
                ],
            ])
            ->add(self::FIELD_TYPE, BlockTypeType::class)
        ;
    }
}
