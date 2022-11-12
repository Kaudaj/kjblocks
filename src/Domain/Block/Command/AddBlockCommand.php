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

use Kaudaj\Module\Blocks\Domain\Block\ValueObject\Name;
use Kaudaj\Module\Blocks\Domain\ValueObject\Json;
use PrestaShop\PrestaShop\Core\Domain\Hook\ValueObject\HookId;

/**
 * Class AddBlockCommand is responsible for adding block data.
 */
class AddBlockCommand extends AbstractBlockCommand
{
    /**
     * @var HookId[]
     */
    private $hooksIds = [];

    /**
     * @var array<int, Name>
     */
    private $localizedNames;

    /**
     * @var string
     */
    private $type;

    /**
     * @var Json|null
     */
    private $options;

    /**
     * @return HookId[]
     */
    public function getHooksIds(): array
    {
        return $this->hooksIds;
    }

    /**
     * @param int[] $hooksIds
     */
    public function setHooksIds(array $hooksIds): self
    {
        foreach ($hooksIds as $hookId) {
            $this->hooksIds[] = new HookId($hookId);
        }

        return $this;
    }

    /**
     * @return array<int, Name>
     */
    public function getLocalizedNames(): array
    {
        return $this->localizedNames;
    }

    /**
     * @param array<int, string> $localizedNames
     */
    public function setLocalizedNames(array $localizedNames): self
    {
        $this->localizedNames = $this->mapLocalizedNames($localizedNames);

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
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
}
