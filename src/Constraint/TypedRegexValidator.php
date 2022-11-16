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
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates specific regex pattern for provided type
 */
class TypedRegexValidator extends ConstraintValidator
{
    public const SELECTOR_PATTERN = '-?[_a-zA-Z]+[_a-zA-Z0-9-]';

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof TypedRegex) {
            throw new UnexpectedTypeException($constraint, TypedRegex::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $pattern = $this->getPattern($constraint->type);

        if (!preg_match($pattern, $value)) {
            $this->context->buildViolation($constraint->message)
                ->setTranslationDomain('Admin.Notifications.Error')
                ->setParameter('%s', $this->formatValue($value))
                ->addViolation()
            ;
        }
    }

    /**
     * Returns regex pattern that depends on type
     *
     * @param string $type
     *
     * @return string
     */
    private function getPattern($type)
    {
        switch ($type) {
            case TypedRegex::TYPE_SELECTOR:
                return '/^' . self::SELECTOR_PATTERN . '*$/';
            case TypedRegex::TYPE_SELECTORS:
                return '/^((' . self::SELECTOR_PATTERN . '),?)*$/';
            default:
                $definedTypes = implode(', ', array_values((new \ReflectionClass(TypedRegex::class))->getConstants()));
                throw new InvalidArgumentException(sprintf('Type "%s" is not defined. Defined types are: %s', $type, $definedTypes));
        }
    }
}
