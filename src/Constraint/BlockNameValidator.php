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

namespace Kaudaj\Module\Blocks\Constraint;

use Symfony\Component\HttpFoundation\File\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class BlockNameValidator extends ConstraintValidator
{
    public const MAX_LENGTH = 255;

    /**
     * @param mixed $name
     */
    public function validate($name, Constraint $constraint): void
    {
        if (!$constraint instanceof BlockName) {
            throw new UnexpectedTypeException($constraint, BlockName::class);
        }

        if (null === $name || '' === $name) {
            return;
        }

        if (!is_string($name)) {
            throw new UnexpectedTypeException($name, 'string');
        }

        if (strlen($name) > self::MAX_LENGTH) {
            $this->context->buildViolation('The block name can\'t exceed ' . self::MAX_LENGTH . ' characters.')
                ->addViolation();
        }

        if (!preg_match('/^[\w-]+$/', $name, $matches)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ name }}', $name)
                ->addViolation();
        }
    }
}
