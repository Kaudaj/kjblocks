<?php
/**
 * Copyright since 2011 Prestarocket
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contact@prestarocket.com so we can send you a copy immediately.
 *
 * @author    Prestarocket <contact@prestarocket.com>
 * @copyright Since 2011 Prestarocket
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

declare(strict_types=1);

namespace Kaudaj\Module\Blocks\Domain\ValueObject;

use Kaudaj\Module\Blocks\Constraint\ConstraintValidatorFactory;
use PrestaShop\PrestaShop\Core\Domain\Exception\DomainConstraintException;
use PrestaShop\PrestaShop\Core\String\CharacterCleaner;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class ValueObject is responsible for providing valid value.
 */
abstract class ValueObject
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    public function __construct()
    {
        $validatorBuilder = Validation::createValidatorBuilder();
        $validatorBuilder->setConstraintValidatorFactory(
            new ConstraintValidatorFactory(new CharacterCleaner())
        );

        $this->validator = $validatorBuilder->getValidator();
    }

    /**
     * @param mixed $value
     * @param Constraint[] $constraints
     *
     * @throws DomainConstraintException
     */
    protected function validate($value, array $constraints, ?string $errorMessage = null): void
    {
        $violations = $this->validator->validate($value, $constraints);

        if (0 === count($violations)) {
            return;
        }

        $violationsMessages = [];

        foreach ($violations as $violation) {
            $violationMessages[] = $violation->getMessage();
        }

        throw new DomainConstraintException(($errorMessage ? "$errorMessage :" : '') . PHP_EOL . implode(PHP_EOL, $violationsMessages));
    }
}
