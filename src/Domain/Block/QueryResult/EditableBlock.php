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

namespace Kaudaj\Module\Blocks\Domain\Block\QueryResult;

use Kaudaj\Module\Blocks\Domain\Block\Exception\BlockException;
use Kaudaj\Module\Blocks\Domain\Block\ValueObject\BlockId;
use Kaudaj\Module\Blocks\Domain\Block\ValueObject\Content;
use Kaudaj\Module\Blocks\Domain\Block\ValueObject\Name;
use PrestaShop\PrestaShop\Core\Domain\Hook\ValueObject\HookId;

/**
 * Transfers block data for editing.
 */
class EditableBlock
{
    /**
     * @var BlockId
     */
    private $blockId;

    /**
     * @var HookId[]
     */
    private $hooksIds = [];

    /**
     * @var array<int, Name>
     */
    private $localizedNames;

    /**
     * @var array<int, Content>
     */
    private $localizedContents;

    /**
     * @param array<int, string> $localizedNames
     * @param array<int, string> $localizedContents
     * @param int[] $hooksIds
     *
     * @throws BlockException
     */
    public function __construct(
        int $blockId,
        array $hooksIds,
        array $localizedNames,
        array $localizedContents
    ) {
        $this->blockId = new BlockId($blockId);

        foreach ($hooksIds as $hookId) {
            $this->hooksIds[] = new HookId($hookId);
        }

        foreach ($localizedNames as $langId => $name) {
            $this->localizedNames[$langId] = new Name($name);
        }

        foreach ($localizedContents as $langId => $name) {
            $this->localizedContents[$langId] = new Content($name);
        }
    }

    public function getBlockId(): BlockId
    {
        return $this->blockId;
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

    /**
     * @return array<int, Content>
     */
    public function getLocalizedContents(): array
    {
        return $this->localizedContents;
    }
}
