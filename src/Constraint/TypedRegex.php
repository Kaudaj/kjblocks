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

namespace Kaudaj\Module\Blocks\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides regex validation by type
 */
class TypedRegex extends Constraint
{
    /**
     * Available types
     */
    public const TYPE_SELECTOR = 'selector';
    public const TYPE_SELECTORS = 'selectors';

    /**
     * @var string
     */
    public $message = '%s is invalid';

    /**
     * @var string
     */
    public $type;

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public function getRequiredOptions(): array
    {
        return ['type'];
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return TypedRegexValidator::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultOption()
    {
        return 'type';
    }
}
