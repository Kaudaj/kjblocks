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

use Kaudaj\Module\Blocks\Constraint as BlockAssert;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

interface BlockInterface
{
    /**
     * @BlockAssert\BlockName
     */
    public function getName(): string;

    public function getLocalizedName(): string;

    public function configureOptions(OptionsResolver $resolver): void;

    /**
     * @param array<string, mixed> $options
     */
    public function render(array $options = []): string;

    /**
     * @Assert\Type(FormTypeInterface::class)
     */
    public function getFormType(): string;

    public function getFormMapper(): string;

    /**
     * @return string[]
     */
    public function getMultiLangOptions(): array;
}
