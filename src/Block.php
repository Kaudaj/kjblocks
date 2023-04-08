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

namespace Kaudaj\Module\Blocks;

use Kaudaj\Module\Blocks\Constraint\ConstraintValidatorFactory;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Core\String\CharacterCleaner;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class Block implements BlockInterface
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var \Smarty
     */
    protected $smarty;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(LegacyContext $legacyContext)
    {
        $this->smarty = clone $legacyContext->getSmarty();
        $this->translator = $legacyContext->getContext()->getTranslator();
    }

    public function getDescription(): string
    {
        return '';
    }

    public function getLogo(): string
    {
        return '';
    }

    protected function getTemplate(): string
    {
        return '';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getTemplateVariables(): array
    {
        return [];
    }

    public function render(): string
    {
        $this->smarty->assign($this->getTemplateVariables());

        $render = $this->smarty->fetch($this->getTemplate());

        return strval(\Hook::exec('filterBlockContent', [
            'content' => $render,
        ])) ?: $render;
    }

    public function setOptions(array $options = []): void
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    private function getValidator(): ValidatorInterface
    {
        if ($this->validator === null) {
            $validatorBuilder = Validation::createValidatorBuilder();
            $validatorBuilder->setConstraintValidatorFactory(
                new ConstraintValidatorFactory(new CharacterCleaner())
            );

            $this->validator = $validatorBuilder->getValidator();
        }

        return $this->validator;
    }

    protected function createIsValidCallable(Constraint ...$constraints): callable
    {
        $validator = $this->getValidator();

        return function ($value) use ($validator, $constraints): bool {
            $violations = $validator->validate($value, $constraints);

            return 0 === $violations->count();
        };
    }

    public function getFormType(): string
    {
        return FormType::class;
    }

    public function getFormMapper(): string
    {
        return 'kaudaj.module.blocks.block.form_mapper';
    }

    public function getMultiLangOptions(): array
    {
        return [];
    }

    public function __clone()
    {
        $this->smarty = clone $this->smarty;
    }
}
