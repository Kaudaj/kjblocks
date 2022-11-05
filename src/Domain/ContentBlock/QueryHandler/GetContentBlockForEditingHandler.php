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

use Exception;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Exception\ContentBlockException;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Query\GetContentBlockForEditing;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\QueryResult\EditableContentBlock;

/**
 * Class GetContentBlockForEditingHandler is responsible for getting the data for content block edit page.
 *
 * @internal
 */
final class GetContentBlockForEditingHandler extends AbstractContentBlockQueryHandler
{
    public function handle(GetContentBlockForEditing $query): EditableContentBlock
    {
        try {
            $contentBlock = $this->getContentBlockEntity(
                $query->getContentBlockId()->getValue()
            );

            $localizedNames = [];
            foreach ($contentBlock->getContentBlockLangs() as $contentBlockLang) {
                $localizedNames[$contentBlockLang->getLang()->getId()] = $contentBlockLang->getName();
            }

            $localizedContents = [];
            foreach ($contentBlock->getContentBlockLangs() as $contentBlockLang) {
                $localizedContents[$contentBlockLang->getLang()->getId()] = $contentBlockLang->getContent();
            }

            $hooksIds = [];
            foreach ($contentBlock->getContentBlockHooks() as $contentBlockHook) {
                $hooksIds[] = $contentBlockHook->getHookId();
            }

            $editableContentBlock = new EditableContentBlock(
                $contentBlock->getId(),
                $hooksIds,
                $localizedNames,
                $localizedContents
            );
        } catch (Exception $e) {
            $message = sprintf(
                'An unexpected error occurred when retrieving content block with id %s',
                var_export($query->getContentBlockId()->getValue(), true)
            );

            throw new ContentBlockException($message, 0, $e);
        }

        return $editableContentBlock
;
    }
}
