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

namespace Kaudaj\Module\Blocks\Domain\Block\ValueObject;

use Kaudaj\Module\Blocks\Domain\ValueObject\ValueObject;
use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\CleanHtml;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class Content is responsible for providing valid block content.
 */
class Content extends ValueObject
{
    /**
     * @var string
     */
    private $content;

    public function __construct(string $content)
    {
        parent::__construct();

        $this->validate($content, [
            new NotBlank(),
            new CleanHtml(),
        ], "$content is not a valid block content");

        $this->content = $content;
    }

    public function getValue(): string
    {
        return $this->content;
    }
}
