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

namespace Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Command;

use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Exception\ContentBlockException;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\ValueObject\ContentBlockId;

/**
 * Class DeleteContentBlockCommand is responsible for deleting content block data.
 */
class DeleteContentBlockCommand
{
    /**
     * @var ContentBlockId
     */
    private $contentBlockId;

    /**
     * @throws ContentBlockException
     */
    public function __construct(int $contentBlockId)
    {
        $this->contentBlockId = new ContentBlockId($contentBlockId);
    }

    public function getContentBlockId(): ContentBlockId
    {
        return $this->contentBlockId;
    }
}
