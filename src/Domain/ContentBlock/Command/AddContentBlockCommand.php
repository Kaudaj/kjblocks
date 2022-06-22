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

namespace Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Command;

use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\ValueObject\Content;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\ValueObject\Name;

/**
 * Class AddContentBlockCommand is responsible for adding content block data.
 */
class AddContentBlockCommand extends AbstractContentBlockCommand
{
    /**
     * @var int
     */
    private $hookId;

    /**
     * @var array<int, Name>
     */
    private $localizedNames;

    /**
     * @var array<int, Content>
     */
    private $localizedContents;

    public function getHookId(): int
    {
        return $this->hookId;
    }

    public function setHookId(int $hookId): self
    {
        $this->hookId = $hookId;

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

    /**
     * @return array<int, Content>
     */
    public function getLocalizedContents(): array
    {
        return $this->localizedContents;
    }

    /**
     * @param array<int, string> $localizedContents
     */
    public function setLocalizedContents(array $localizedContents): self
    {
        $this->localizedContents = $this->mapLocalizedContents($localizedContents);

        return $this;
    }
}
