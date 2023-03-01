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

namespace Kaudaj\Module\Blocks\Domain\BlockGroup\QueryHandler;

use Kaudaj\Module\Blocks\Domain\BlockGroup\Exception\BlockGroupException;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Query\GetBlockGroupForEditing;
use Kaudaj\Module\Blocks\Domain\BlockGroup\QueryResult\EditableBlockGroup;

/**
 * @internal
 */
final class GetBlockGroupForEditingHandler extends AbstractBlockGroupQueryHandler
{
    public function handle(GetBlockGroupForEditing $query): EditableBlockGroup
    {
        try {
            $blockGroup = $this->getBlockGroupEntity(
                $query->getBlockGroupId()->getValue()
            );

            $localizedNames = [];
            foreach ($blockGroup->getBlockGroupLangs() as $blockGroupLang) {
                $localizedNames[$blockGroupLang->getLang()->getId()] = $blockGroupLang->getName();
            }

            $hooksIds = [];
            foreach ($blockGroup->getBlockGroupHooks() as $blockGroupHook) {
                $hooksIds[] = $blockGroupHook->getHookId();
            }

            $editableBlockGroup = new EditableBlockGroup(
                $blockGroup->getId(),
                $hooksIds,
                $localizedNames
            );
        } catch (\Exception $e) {
            $message = sprintf(
                'An unexpected error occurred when retrieving block group with id %s',
                var_export($query->getBlockGroupId()->getValue(), true)
            );

            throw new BlockGroupException($message, 0, $e);
        }

        return $editableBlockGroup;
    }
}
