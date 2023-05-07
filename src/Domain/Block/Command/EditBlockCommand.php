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

namespace Kaudaj\Module\Blocks\Domain\Block\Command;

use Kaudaj\Module\Blocks\Domain\Block\Exception\BlockException;
use Kaudaj\Module\Blocks\Domain\Block\ValueObject\BlockId;
use Kaudaj\Module\Blocks\Domain\Block\ValueObject\Name;
use Kaudaj\Module\Blocks\Domain\BlockGroup\ValueObject\BlockGroupId;
use Kaudaj\Module\Blocks\Domain\ValueObject\Json;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;

/**
 * Class EditBlockCommand is responsible for editing block data.
 */
class EditBlockCommand extends AbstractBlockCommand
{
    /**
     * @var BlockId
     */
    private $blockId;

    /**
     * @var BlockGroupId[]|null
     */
    private $blockGroupsIds;

    /**
     * @var array<int, Name>|null
     */
    private $localizedNames;

    /**
     * @var string|null
     */
    private $type;

    /**
     * @var Json|null
     */
    private $options;

    /**
     * @var ShopConstraint
     */
    private $shopConstraint;

    /**
     * @throws BlockException
     */
    public function __construct(int $blockId)
    {
        parent::__construct();

        $this->blockId = new BlockId($blockId);
    }

    public function getBlockId(): BlockId
    {
        return $this->blockId;
    }

    /**
     * @return BlockGroupId[]|null
     */
    public function getBlockGroupsIds(): ?array
    {
        return $this->blockGroupsIds;
    }

    /**
     * @param int[]|null $blockGroupsIds
     */
    public function setBlockGroupsIds(?array $blockGroupsIds): self
    {
        if ($blockGroupsIds) {
            $blockGroupsIds = array_map(function (int $blockGroupsId): BlockGroupId {
                return new BlockGroupId($blockGroupsId);
            }, $blockGroupsIds);
        }

        $this->blockGroupsIds = $blockGroupsIds;

        return $this;
    }

    /**
     * @return array<int, Name>|null
     */
    public function getLocalizedNames(): ?array
    {
        return $this->localizedNames;
    }

    /**
     * @param array<int, string>|null $localizedNames
     */
    public function setLocalizedNames(?array $localizedNames): self
    {
        if ($localizedNames !== null) {
            $localizedNames = $this->mapLocalizedNames($localizedNames);
        }

        $this->localizedNames = $localizedNames;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getOptions(): ?Json
    {
        return $this->options;
    }

    public function setOptions(?string $options): self
    {
        if ($options !== null) {
            $options = new Json($options);
        }

        $this->options = $options;

        return $this;
    }

    public function getShopConstraint(): ShopConstraint
    {
        return $this->shopConstraint;
    }

    public function setShopConstraint(ShopConstraint $shopConstraint): self
    {
        $this->shopConstraint = $shopConstraint;

        return $this;
    }
}
