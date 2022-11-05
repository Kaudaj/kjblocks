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

namespace Kaudaj\Module\Blocks\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kaudaj\Module\Blocks\Repository\BlockRepository;

/**
 * @ORM\Table(name=BlockRepository::TABLE_NAME_WITH_PREFIX)
 * @ORM\Entity(repositoryClass=BlockRepository::class)
 */
class Block
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id_block", type="integer")
     */
    private $id;

    /**
     * @var Collection<int, BlockHook>
     *
     * @ORM\OneToMany(targetEntity=BlockHook::class, cascade={"persist", "remove"}, mappedBy="block")
     */
    private $blockHooks;

    /**
     * @var Collection<int, BlockLang>
     *
     * @ORM\OneToMany(targetEntity=BlockLang::class, cascade={"persist", "remove"}, mappedBy="block")
     */
    private $blockLangs;

    public function __construct()
    {
        $this->blockLangs = new ArrayCollection();
        $this->blockHooks = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, BlockHook>
     */
    public function getBlockHooks(): Collection
    {
        return $this->blockHooks;
    }

    public function getBlockHook(int $hookId): ?BlockHook
    {
        foreach ($this->blockHooks as $blockHook) {
            if ($hookId == $blockHook->getHookId()) {
                return $blockHook;
            }
        }

        return null;
    }

    public function addBlockHook(BlockHook $blockHook): self
    {
        if (!$this->blockHooks->contains($blockHook)) {
            $this->blockHooks[] = $blockHook;
            $blockHook->setBlock($this);
        }

        return $this;
    }

    public function removeBlockHook(BlockHook $blockLang): self
    {
        $this->blockHooks->removeElement($blockLang);

        return $this;
    }

    /**
     * @return Collection<int, BlockLang>
     */
    public function getBlockLangs(): Collection
    {
        return $this->blockLangs;
    }

    public function getBlockLang(int $langId): ?BlockLang
    {
        foreach ($this->blockLangs as $blockLang) {
            if ($langId == $blockLang->getLang()->getId()) {
                return $blockLang;
            }
        }

        return null;
    }

    public function addBlockLang(BlockLang $blockLang): self
    {
        if (!$this->blockLangs->contains($blockLang)) {
            $this->blockLangs[] = $blockLang;
            $blockLang->setBlock($this);
        }

        return $this;
    }

    public function removeBlockLang(BlockLang $blockLang): self
    {
        $this->blockLangs->removeElement($blockLang);

        return $this;
    }
}
