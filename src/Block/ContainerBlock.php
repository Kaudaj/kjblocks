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

use Kaudaj\Module\Blocks\Block;
use Kaudaj\Module\Blocks\Constraint\TypedRegex;
use Kaudaj\Module\Blocks\Form\Type\Block\ContainerBlockType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Url;

class ContainerBlock extends Block
{
    public const OPTION_WIDTH = 'width';
    public const OPTION_HEIGHT = 'height';
    public const OPTION_CLASSES = 'classes';
    public const OPTION_IDENTIFIER = 'identifier';
    public const OPTION_BACKGROUND_IMAGE = 'background_image';

    public function getName(): string
    {
        return 'container';
    }

    public function getLocalizedName(): string
    {
        return $this->translator->trans('Container', [], 'Modules.Kjblocks.Admin');
    }

    protected function getTemplate(): string
    {
        return 'module:kjblocks/views/templates/front/blocks/container.tpl';
    }

    protected function getTemplateVariables(array $options): array
    {
        $variables = parent::getTemplateVariables($options);

        $inlineStyle = '';

        if (key_exists(self::OPTION_WIDTH, $options)) {
            $width = intval($options[self::OPTION_WIDTH]);
            $inlineStyle .= "width: {$width}px;";
        }

        if (key_exists(self::OPTION_HEIGHT, $options)) {
            $height = intval($options[self::OPTION_HEIGHT]);
            $inlineStyle .= "height: {$height}px;";
        }

        if (key_exists(self::OPTION_BACKGROUND_IMAGE, $options)) {
            $url = strval($options[self::OPTION_BACKGROUND_IMAGE]);
            $inlineStyle .= "background-image: url(\"{$url}\");";
        }

        if (!empty($inlineStyle)) {
            $variables['inline_style'] = $inlineStyle;
        }

        if (key_exists(self::OPTION_IDENTIFIER, $options)) {
            $variables[self::OPTION_IDENTIFIER] = strval($options[self::OPTION_IDENTIFIER]);
        }

        $classes = [str_replace('_', '-', $this->getName())];

        if (key_exists(self::OPTION_CLASSES, $options)) {
            $classes = array_merge($classes, explode(',', strval($options[self::OPTION_CLASSES])));
        }

        $variables[self::OPTION_CLASSES] = $classes;

        return $variables;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $dimensionIsValidCallable = $this->createIsValidCallable(
            new Type('int'),
            new GreaterThan(0)
        );

        $resolver
            ->setDefined([
                self::OPTION_WIDTH,
                self::OPTION_HEIGHT,
                self::OPTION_CLASSES,
                self::OPTION_IDENTIFIER,
                self::OPTION_BACKGROUND_IMAGE,
            ])

            ->setAllowedTypes(self::OPTION_WIDTH, 'int')
            ->setAllowedTypes(self::OPTION_HEIGHT, 'int')
            ->setAllowedTypes(self::OPTION_IDENTIFIER, 'string')
            ->setAllowedTypes(self::OPTION_CLASSES, 'string')
            ->setAllowedTypes(self::OPTION_BACKGROUND_IMAGE, 'string')

            ->setAllowedValues(self::OPTION_WIDTH, $dimensionIsValidCallable)
            ->setAllowedValues(self::OPTION_HEIGHT, $dimensionIsValidCallable)
            ->setAllowedValues(self::OPTION_IDENTIFIER, $this->createIsValidCallable(
                new TypedRegex(TypedRegex::TYPE_SELECTOR)
            ))
            ->setAllowedValues(self::OPTION_CLASSES, $this->createIsValidCallable(
                new TypedRegex(TypedRegex::TYPE_SELECTORS)
            ))
            ->setAllowedValues(self::OPTION_BACKGROUND_IMAGE, $this->createIsValidCallable(
                new Url()
            ))
        ;
    }

    public function getFormType(): string
    {
        return ContainerBlockType::class;
    }

    public function getFormMapper(): string
    {
        return 'kaudaj.module.blocks.form.mapper.container';
    }
}
