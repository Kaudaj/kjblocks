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
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

if (version_compare(_PS_VERSION_, '8.0', '<') && !interface_exists('Symfony\Contracts\Translation\TranslatorInterface')) {
    class_alias('Symfony\Component\Translation\TranslatorInterface', 'Symfony\Contracts\Translation\TranslatorInterface');
}

abstract class Block implements BlockInterface
{
    public const OPTION_ID = 'id';

    public const FILTER_CONTENT_HOOK = 'filterBlockContent';

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var LegacyContext
     */
    protected $legacyContext;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var int|null
     */
    protected $id;

    public function __construct(LegacyContext $legacyContext)
    {
        $this->legacyContext = $legacyContext;
        $this->translator = $legacyContext->getContext()->getTranslator(); // @phpstan-ignore-line
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
        return [self::OPTION_ID => $this->id];
    }

    public function render(): string
    {
        $module = \Module::getInstanceByName($this->getModuleName());
        if (!$module) {
            throw new \RuntimeException("Module {$this->getModuleName()} not found");
        }

        /** @var \KJBlocks|false */
        $kjblocks = \Module::getInstanceByName('kjblocks');
        if (!$kjblocks) {
            throw new \RuntimeException('Module kjblocks not found');
        }

        $smarty = $this->legacyContext->getSmarty();

        $template = $this->getTemplate();
        $cacheId = $this->id ? "{$this->getCacheId()}|{$kjblocks->getContextCacheId()}" : null;
        $isCached = $this->id && $module->isCached($template, $cacheId);

        if (!$isCached) {
            /** @var array<string, mixed> */
            $currentVars = $smarty->getTemplateVars();
            $smarty->assign($this->getTemplateVariables());
        }

        $render = $module->fetch($template, $cacheId);

        if (!$isCached) {
            $smarty->clearAllAssign();
            $smarty->assign($currentVars);
        }

        return (string) \Hook::exec(self::FILTER_CONTENT_HOOK, [
            'content' => $render,
        ]) ?: $render;
    }

    protected function getModuleName(): string
    {
        return 'kjblocks';
    }

    protected function getCacheId(): string
    {
        return "{$this->getModuleName()}|{$this->id}";
    }

    public function setOptions(array $options = []): void
    {
        if (key_exists(self::OPTION_ID, $options)) {
            $this->id = (int) $options[self::OPTION_ID];
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined(self::OPTION_ID)
            ->setAllowedTypes(self::OPTION_ID, 'int')
            ->setAllowedValues(self::OPTION_ID, $this->createIsValidCallable(
                new GreaterThan(0)
            ))
        ;
    }

    private function getValidator(): ValidatorInterface
    {
        if ($this->validator === null) {
            $validatorBuilder = Validation::createValidatorBuilder();
            $validatorBuilder->setConstraintValidatorFactory(new ConstraintValidatorFactory());

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
}
