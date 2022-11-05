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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kaudaj\Module\ContentBlocks\Repository\ContentBlockRepository;

/**
 * @ORM\Table(name=ContentBlockRepository::TABLE_NAME_WITH_PREFIX)
 * @ORM\Entity(repositoryClass=ContentBlockRepository::class)
 */
class ContentBlock
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id_content_block", type="integer")
     */
    private $id;

    /**
     * @var Collection<int, ContentBlockHook>
     *
     * @ORM\OneToMany(targetEntity=ContentBlockHook::class, cascade={"persist", "remove"}, mappedBy="contentBlock")
     */
    private $contentBlockHooks;

    /**
     * @var Collection<int, ContentBlockLang>
     *
     * @ORM\OneToMany(targetEntity=ContentBlockLang::class, cascade={"persist", "remove"}, mappedBy="contentBlock")
     */
    private $contentBlockLangs;

    public function __construct()
    {
        $this->contentBlockLangs = new ArrayCollection();
        $this->contentBlockHooks = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, ContentBlockHook>
     */
    public function getContentBlockHooks(): Collection
    {
        return $this->contentBlockHooks;
    }

    public function getContentBlockHook(int $hookId): ?ContentBlockHook
    {
        foreach ($this->contentBlockHooks as $contentBlockHook) {
            if ($hookId == $contentBlockHook->getHookId()) {
                return $contentBlockHook;
            }
        }

        return null;
    }

    public function addContentBlockHook(ContentBlockHook $contentBlockHook): self
    {
        if (!$this->contentBlockHooks->contains($contentBlockHook)) {
            $this->contentBlockHooks[] = $contentBlockHook;
            $contentBlockHook->setContentBlock($this);
        }

        return $this;
    }

    public function removeContentBlockHook(ContentBlockHook $contentBlockLang): self
    {
        $this->contentBlockHooks->removeElement($contentBlockLang);

        return $this;
    }

    /**
     * @return Collection<int, ContentBlockLang>
     */
    public function getContentBlockLangs(): Collection
    {
        return $this->contentBlockLangs;
    }

    public function getContentBlockLang(int $langId): ?ContentBlockLang
    {
        foreach ($this->contentBlockLangs as $contentBlockLang) {
            if ($langId == $contentBlockLang->getLang()->getId()) {
                return $contentBlockLang;
            }
        }

        return null;
    }

    public function addContentBlockLang(ContentBlockLang $contentBlockLang): self
    {
        if (!$this->contentBlockLangs->contains($contentBlockLang)) {
            $this->contentBlockLangs[] = $contentBlockLang;
            $contentBlockLang->setContentBlock($this);
        }

        return $this;
    }

    public function removeContentBlockLang(ContentBlockLang $contentBlockLang): self
    {
        $this->contentBlockLangs->removeElement($contentBlockLang);

        return $this;
    }
}
