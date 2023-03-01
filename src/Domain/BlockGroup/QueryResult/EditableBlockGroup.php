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

namespace Kaudaj\Module\Blocks\Domain\BlockGroup\QueryResult;

use Kaudaj\Module\Blocks\Domain\BlockGroup\Exception\BlockGroupException;
use Kaudaj\Module\Blocks\Domain\BlockGroup\ValueObject\BlockGroupId;
use Kaudaj\Module\Blocks\Domain\BlockGroup\ValueObject\Name;
use PrestaShop\PrestaShop\Core\Domain\Hook\ValueObject\HookId;

/**
 * Transfersblock groupdata for editing.
 */
class EditableBlockGroup
{
    /**
     * @var BlockGroupId
     */
    private $blockGroupId;

    /**
     * @var HookId[]
     */
    private $hooksIds = [];

    /**
     * @var array<int, Name>
     */
    private $localizedNames;

    /**
     * @param array<int, string> $localizedNames
     * @param int[] $hooksIds
     *
     * @throws BlockGroupException
     */
    public function __construct(
        int $blockGroupId,
        array $hooksIds,
        array $localizedNames
    ) {
        $this->blockGroupId = new BlockGroupId($blockGroupId);

        foreach ($hooksIds as $hookId) {
            $this->hooksIds[] = new HookId($hookId);
        }

        foreach ($localizedNames as $langId => $name) {
            $this->localizedNames[$langId] = new Name($name);
        }
    }

    public function getBlockGroupId(): BlockGroupId
    {
        return $this->blockGroupId;
    }

    /**
     * @return HookId[]
     */
    public function getHooksIds(): array
    {
        return $this->hooksIds;
    }

    /**
     * @return array<int, Name>
     */
    public function getLocalizedNames(): array
    {
        return $this->localizedNames;
    }
}
