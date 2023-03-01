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
use Kaudaj\Module\Blocks\Domain\Block\ValueObject\Name;
use Kaudaj\Module\Blocks\Domain\BlockGroup\ValueObject\BlockGroupId;
use Kaudaj\Module\Blocks\Domain\ValueObject\Json;

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
     * @var string
     */
    private $type;

    /**
     * @var Json|null
     */
    private $options;

    /**
     * @var BlockGroupId[]
     */
    private $blockGroupsIds = [];

    /**
     * @var array<int, Name>
     */
    private $localizedNames;

    /**
     * @param array<int, string> $localizedNames
     * @param int[] $blockGroupsIds
     *
     * @throws BlockException
     */
    public function __construct(
        int $blockId,
        string $type,
        ?string $options,
        array $blockGroupsIds,
        array $localizedNames
    ) {
        $this->blockId = new BlockId($blockId);
        $this->type = $type;
        $this->options = $options !== null ? new Json($options) : null;

        foreach ($blockGroupsIds as $hookId) {
            $this->blockGroupsIds[] = new BlockGroupId($hookId);
        }

        foreach ($localizedNames as $langId => $name) {
            $this->localizedNames[$langId] = new Name($name);
        }
    }

    public function getBlockId(): BlockId
    {
        return $this->blockId;
    }

    /**
     * @return BlockGroupId[]
     */
    public function getBlockGroupsIds(): array
    {
        return $this->blockGroupsIds;
    }

    /**
     * @return array<int, Name>
     */
    public function getLocalizedNames(): array
    {
        return $this->localizedNames;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getOptions(): ?Json
    {
        return $this->options;
    }
}
