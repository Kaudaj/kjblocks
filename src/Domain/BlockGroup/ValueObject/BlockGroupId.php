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

namespace Kaudaj\Module\Blocks\Domain\BlockGroup\ValueObject;

use Kaudaj\Module\Blocks\Domain\ValueObject\ValueObject;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;

class BlockGroupId extends ValueObject
{
    /**
     * @var int
     */
    private $groupId;

    public function __construct(int $groupId)
    {
        parent::__construct();

        $this->validate($groupId, [
            new NotBlank(),
            new GreaterThan(0),
        ], "$groupId is not a valid group id");

        $this->groupId = $groupId;
    }

    public function getValue(): int
    {
        return $this->groupId;
    }
}
