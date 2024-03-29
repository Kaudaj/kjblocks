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

namespace Kaudaj\Module\Blocks\Domain\BlockGroup\Command;

use Kaudaj\Module\Blocks\Domain\BlockGroup\Exception\BlockGroupException;
use Kaudaj\Module\Blocks\Domain\BlockGroup\ValueObject\BlockGroupId;
use Kaudaj\Module\Blocks\Domain\BlockGroup\ValueObject\Name;
use PrestaShop\PrestaShop\Core\Domain\Hook\ValueObject\HookId;

class EditBlockGroupCommand extends AbstractBlockGroupCommand
{
    /**
     * @var BlockGroupId
     */
    private $blockGroupId;

    /**
     * @var HookId[]|null
     */
    private $hooksIds;

    /**
     * @var array<int, Name>|null
     */
    private $localizedNames;

    /**
     * @throws BlockGroupException
     */
    public function __construct(int $blockGroupId)
    {
        parent::__construct();

        $this->blockGroupId = new BlockGroupId($blockGroupId);
    }

    public function getBlockGroupId(): BlockGroupId
    {
        return $this->blockGroupId;
    }

    /**
     * @return HookId[]|null
     */
    public function getHooksIds(): ?array
    {
        return $this->hooksIds;
    }

    /**
     * @param int[]|null $hooksIds
     */
    public function setHooksIds(?array $hooksIds): self
    {
        if ($hooksIds) {
            $hooksIds = array_map(function (int $hookId): HookId {
                return new HookId($hookId);
            }, $hooksIds);
        }

        $this->hooksIds = $hooksIds;

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
}
