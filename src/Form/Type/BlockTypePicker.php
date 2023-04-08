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
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BlockTypePicker extends TranslatorAwareType
{
    public const OPTION_BLOCK_TYPES = 'block_types';

    /**
     * @param FormInterface<string, mixed> $form
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        if (!is_array($options[self::OPTION_BLOCK_TYPES])) {
            return;
        }

        $view->vars[self::OPTION_BLOCK_TYPES] = $this->presentBlockTypesForForm($options[self::OPTION_BLOCK_TYPES]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'row_attr' => ['class' => str_replace('_', '-', $this->getBlockPrefix())],
                'compound' => false,
             ])
            ->setRequired(self::OPTION_BLOCK_TYPES)
            ->setAllowedTypes(self::OPTION_BLOCK_TYPES, 'array')
        ;
    }

    /**
     * @param array<string, BlockInterface[]> $blockTypes
     *
     * @return array<string, mixed>
     */
    protected function presentBlockTypesForForm(array $blockTypes): array
    {
        $presentedBlockTypes = [];

        foreach ($blockTypes as $moduleName => $moduleBlocks) {
            $module = \Module::getInstanceByName($moduleName);
            if (!$module) {
                continue;
            }

            $moduleBlocksTypes = [];
            foreach ($moduleBlocks as $block) {
                $moduleBlocksTypes[$block->getName()] = [
                    'displayName' => $block->getLocalizedName(),
                    'description' => $block->getDescription(),
                    'logo' => $block->getLogo(),
                ];
            }

            $presentedBlockTypes[$moduleName] = [
                'displayName' => $module->displayName,
                'logo' => $module->getPathUri() . 'logo.png',
                'blocks' => $moduleBlocksTypes,
            ];
        }

        return $presentedBlockTypes;
    }
}
