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

    /**
     * @var int
     */
    protected $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * @var string[]
     */
    protected $classes = [];

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $backgroundImage;

    public function getName(): string
    {
        return 'container';
    }

    public function getDescription(): string
    {
        return $this->translator->trans('Simple container, designed to be a base to create boxed blocks', [], 'Modules.Kjblocks.Admin');
    }

    public function getLocalizedName(): string
    {
        return $this->translator->trans('Container', [], 'Modules.Kjblocks.Admin');
    }

    protected function getTemplate(): string
    {
        return 'module:kjblocks/views/templates/front/blocks/container.tpl';
    }

    protected function getTemplateVariables(): array
    {
        $variables = parent::getTemplateVariables();

        if ($this->width) {
            $variables[self::OPTION_WIDTH] = $this->width;
        }

        if ($this->height) {
            $variables[self::OPTION_HEIGHT] = $this->height;
        }

        if ($this->backgroundImage) {
            $variables[self::OPTION_BACKGROUND_IMAGE] = $this->backgroundImage;
        }

        if ($this->identifier) {
            $variables[self::OPTION_IDENTIFIER] = $this->identifier;
        }

        $classes = ['block', "block-{$this->id}", str_replace('_', '-', $this->getName())];
        $classes = array_merge($classes, $this->classes);

        $variables[self::OPTION_CLASSES] = $classes;

        return $variables;
    }

    public function setOptions(array $options = []): void
    {
        parent::setOptions($options);

        if (key_exists(self::OPTION_WIDTH, $options)) {
            $this->width = intval($options[self::OPTION_WIDTH]);
        }

        if (key_exists(self::OPTION_HEIGHT, $options)) {
            $this->height = intval($options[self::OPTION_HEIGHT]);
        }

        if (key_exists(self::OPTION_BACKGROUND_IMAGE, $options)) {
            $this->backgroundImage = strval($options[self::OPTION_BACKGROUND_IMAGE]);
        }

        if (key_exists(self::OPTION_IDENTIFIER, $options)) {
            $this->identifier = strval($options[self::OPTION_IDENTIFIER]);
        }

        if (key_exists(self::OPTION_CLASSES, $options)) {
            $this->classes = explode(',', strval($options[self::OPTION_CLASSES]));
        }
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
