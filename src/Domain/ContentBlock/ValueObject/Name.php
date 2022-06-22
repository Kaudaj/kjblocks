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

namespace Kaudaj\Module\ContentBlocks\Domain\ContentBlock\ValueObject;

use Kaudaj\Module\ContentBlocks\Domain\ValueObject\ValueObject;
use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\TypedRegex;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class Name is responsible for providing valid block name.
 */
class Name extends ValueObject
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        parent::__construct();

        $this->validate($name, [
            new NotBlank(),
            new TypedRegex(TypedRegex::TYPE_GENERIC_NAME),
        ], "$name is not a valid block name");

        $this->name = $name;
    }

    public function getValue(): string
    {
        return $this->name;
    }
}
