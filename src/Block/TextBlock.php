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

namespace Kaudaj\Module\Blocks\Block;

use Kaudaj\Module\Blocks\Form\Type\Block\TextBlockType;
use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\CleanHtml;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextBlock extends ContainerBlock
{
    /**
     * @var string
     */
    protected $text;

    public const OPTION_TEXT = 'text';

    public function getName(): string
    {
        return 'text';
    }

    public function getDescription(): string
    {
        return $this->translator->trans('Display a formatted text', [], 'Modules.Kjblocks.Admin');
    }

    public function getLocalizedName(): string
    {
        return $this->translator->trans('Text', [], 'Modules.Kjblocks.Admin');
    }

    protected function getTemplate(): string
    {
        return 'module:kjblocks/views/templates/front/blocks/text.tpl';
    }

    protected function getTemplateVariables(): array
    {
        $variables = parent::getTemplateVariables();

        $variables[self::OPTION_TEXT] = $this->text;

        return $variables;
    }

    public function setOptions(array $options = []): void
    {
        parent::setOptions($options);

        $this->text = strval($options[self::OPTION_TEXT]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->setRequired(self::OPTION_TEXT)
            ->setAllowedTypes(self::OPTION_TEXT, 'string')
            ->setAllowedValues(self::OPTION_TEXT, $this->createIsValidCallable(
                new CleanHtml()
            ))
        ;
    }

    public function getFormType(): string
    {
        return TextBlockType::class;
    }

    public function getMultiLangOptions(): array
    {
        return [self::OPTION_TEXT];
    }
}
