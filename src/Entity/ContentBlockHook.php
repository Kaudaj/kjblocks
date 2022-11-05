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

namespace Kaudaj\Module\ContentBlocks\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kaudaj\Module\ContentBlocks\Repository\ContentBlockHookRepository;

/**
 * @ORM\Table(name=ContentBlockHookRepository::TABLE_NAME_WITH_PREFIX)
 * @ORM\Entity(repositoryClass=ContentBlockHookRepository::class)
 */
class ContentBlockHook
{
    /**
     * @var ContentBlock
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=ContentBlock::class, inversedBy="contentBlockHooks")
     * @ORM\JoinColumn(name="id_content_block", referencedColumnName="id_content_block", nullable=false, onDelete="CASCADE")
     */
    private $contentBlock;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id_hook", type="integer", options={"unsigned": true})
     */
    private $hookId;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"unsigned": true})
     */
    private $position;

    public function getContentBlock(): ContentBlock
    {
        return $this->contentBlock;
    }

    public function setContentBlock(ContentBlock $contentBlock): self
    {
        $this->contentBlock = $contentBlock;

        return $this;
    }

    public function getHookId(): int
    {
        return $this->hookId;
    }

    public function setHookId(int $hookId): self
    {
        $this->hookId = $hookId;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }
}
