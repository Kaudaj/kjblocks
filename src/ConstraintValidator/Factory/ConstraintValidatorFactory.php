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

namespace Kaudaj\Module\Blocks\ConstraintValidator\Factory;

use PrestaShop\PrestaShop\Core\ConstraintValidator\CleanHtmlValidator;
use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\CleanHtml;
use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\TypedRegex;
use PrestaShop\PrestaShop\Core\ConstraintValidator\TypedRegexValidator;
use PrestaShop\PrestaShop\Core\String\CharacterCleaner;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactory as BaseConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;

class ConstraintValidatorFactory extends BaseConstraintValidatorFactory implements ConstraintValidatorFactoryInterface
{
    /**
     * @var CharacterCleaner
     */
    private $characterCleaner;

    /**
     * CustomerNameValidatorFactory constructor.
     *
     * @param CharacterCleaner $characterCleaner
     */
    public function __construct(CharacterCleaner $characterCleaner)
    {
        parent::__construct();

        $this->characterCleaner = $characterCleaner;
    }

    /**
     * @param Constraint $constraint
     *
     * @return ConstraintValidatorInterface
     */
    public function getInstance(Constraint $constraint)
    {
        if ($constraint instanceof TypedRegex) {
            return new TypedRegexValidator($this->characterCleaner);
        }

        if ($constraint instanceof CleanHtml) {
            return new CleanHtmlValidator(false);
        }

        return parent::getInstance($constraint);
    }
}
