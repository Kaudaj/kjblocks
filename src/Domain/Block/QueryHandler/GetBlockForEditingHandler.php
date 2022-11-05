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

namespace Kaudaj\Module\Blocks\Domain\Block\QueryHandler;

use Exception;
use Kaudaj\Module\Blocks\Domain\Block\Exception\BlockException;
use Kaudaj\Module\Blocks\Domain\Block\Query\GetBlockForEditing;
use Kaudaj\Module\Blocks\Domain\Block\QueryResult\EditableBlock;

/**
 * Class GetBlockForEditingHandler is responsible for getting the data for block edit page.
 *
 * @internal
 */
final class GetBlockForEditingHandler extends AbstractBlockQueryHandler
{
    public function handle(GetBlockForEditing $query): EditableBlock
    {
        try {
            $block = $this->getBlockEntity(
                $query->getBlockId()->getValue()
            );

            $localizedNames = [];
            foreach ($block->getBlockLangs() as $blockLang) {
                $localizedNames[$blockLang->getLang()->getId()] = $blockLang->getName();
            }

            $localizedContents = [];
            foreach ($block->getBlockLangs() as $blockLang) {
                $localizedContents[$blockLang->getLang()->getId()] = $blockLang->getContent();
            }

            $hooksIds = [];
            foreach ($block->getBlockHooks() as $blockHook) {
                $hooksIds[] = $blockHook->getHookId();
            }

            $editableBlock = new EditableBlock(
                $block->getId(),
                $hooksIds,
                $localizedNames,
                $localizedContents
            );
        } catch (Exception $e) {
            $message = sprintf(
                'An unexpected error occurred when retrieving block with id %s',
                var_export($query->getBlockId()->getValue(), true)
            );

            throw new BlockException($message, 0, $e);
        }

        return $editableBlock
;
    }
}