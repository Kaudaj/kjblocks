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

use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Exception\ContentBlockException;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\ValueObject\Content;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\ValueObject\ContentBlockId;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\ValueObject\Name;

/**
 * Class EditContentBlockCommand is responsible for editing content block data.
 */
class EditContentBlockCommand extends AbstractContentBlockCommand
{
    /**
     * @var ContentBlockId
     */
    private $contentBlockId;

    /**
     * @var int|null
     */
    private $hookId;

    /**
     * @var array<int, Name>|null
     */
    private $localizedNames;

    /**
     * @var array<int, Content>|null
     */
    private $localizedContents;

    /**
     * @throws ContentBlockException
     */
    public function __construct(int $contentBlockId)
    {
        $this->contentBlockId = new ContentBlockId($contentBlockId);
    }

    public function getContentBlockId(): ContentBlockId
    {
        return $this->contentBlockId;
    }

    public function getHookId(): ?int
    {
        return $this->hookId;
    }

    public function setHookId(?int $hookId): self
    {
        $this->hookId = $hookId;

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

    /**
     * @return array<int, Content>|null
     */
    public function getLocalizedContents(): ?array
    {
        return $this->localizedContents;
    }

    /**
     * @param array<int, string>|null $localizedContents
     */
    public function setLocalizedContents(?array $localizedContents): self
    {
        if ($localizedContents !== null) {
            $localizedContents = $this->mapLocalizedContents($localizedContents);
        }

        $this->localizedContents = $localizedContents;

        return $this;
    }
}
