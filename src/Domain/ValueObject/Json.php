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

use PrestaShop\PrestaShop\Core\Domain\Exception\DomainConstraintException;

/**
 * Class Json is responsible for providing valid json string.
 */
class Json extends ValueObject
{
    /**
     * @var string
     */
    private $json;

    public function __construct(string $json)
    {
        parent::__construct();

        json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new DomainConstraintException("$json is not a valid string");
        }

        $this->json = $json;
    }

    public function getValue(): string
    {
        return $this->json;
    }
}