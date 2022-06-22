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

namespace Kaudaj\Module\ContentBlocks\Domain\ContentBlock\QueryHandler;

use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Exception\ContentBlockException;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Exception\ContentBlockNotFoundException;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Query\GetContentBlock;
use Kaudaj\Module\ContentBlocks\Entity\ContentBlock;
use PrestaShopException;

/**
 * Class GetContentBlockHandler is responsible for getting content block entity.
 *
 * @internal
 */
final class GetContentBlockHandler extends AbstractContentBlockQueryHandler
{
    /**
     * @throws PrestaShopException
     * @throws ContentBlockNotFoundException
     */
    public function handle(GetContentBlock $query): ContentBlock
    {
        try {
            $contentBlock = $this->getContentBlockEntity(
                $query->getContentBlockId()->getValue()
            );
        } catch (PrestaShopException $e) {
            $message = sprintf(
                'An unexpected error occurred when retrieving content block with id %s',
                var_export($query->getContentBlockId()->getValue(), true)
            );

            throw new ContentBlockException($message, 0, $e);
        }

        return $contentBlock;
    }
}
