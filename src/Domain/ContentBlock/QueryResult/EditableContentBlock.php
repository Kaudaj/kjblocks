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

namespace Kaudaj\Module\ContentBlocks\Domain\ContentBlock\QueryResult;

use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Exception\ContentBlockException;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\ValueObject\Content;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\ValueObject\ContentBlockId;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\ValueObject\Name;

/**
 * Transfers content block data for editing.
 */
class EditableContentBlock
{
    /**
     * @var ContentBlockId
     */
    private $contentBlockId;

    /**
     * @var string
     */
    private $hookName;

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
     *
     * @throws ContentBlockException
     */
    public function __construct(
        int $contentBlockId,
        string $hookName,
        array $localizedNames,
        array $localizedContents
    ) {
        $this->contentBlockId = new ContentBlockId($contentBlockId);
        $this->hookName = $hookName;

        foreach ($localizedNames as $langId => $name) {
            $this->localizedNames[$langId] = new Name($name);
        }

        foreach ($localizedContents as $langId => $name) {
            $this->localizedContents[$langId] = new Content($name);
        }
    }

    public function getContentBlockId(): ContentBlockId
    {
        return $this->contentBlockId;
    }

    public function getHookName(): string
    {
        return $this->hookName;
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
