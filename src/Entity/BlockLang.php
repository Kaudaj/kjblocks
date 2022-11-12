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

use Doctrine\ORM\Mapping as ORM;
use Kaudaj\Module\Blocks\Repository\BlockRepository;
use PrestaShopBundle\Entity\Lang;

/**
 * @ORM\Table(name=BlockRepository::LANG_TABLE_NAME_WITH_PREFIX)
 * @ORM\Entity()
 */
class BlockLang
{
    /**
     * @var Block
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=Block::class, inversedBy="blockLangs")
     * @ORM\JoinColumn(name="id_block", referencedColumnName="id_block", nullable=false, onDelete="CASCADE")
     */
    private $block;

    /**
     * @var Lang
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=Lang::class)
     * @ORM\JoinColumn(name="id_lang", referencedColumnName="id_lang", nullable=false, onDelete="CASCADE")
     */
    private $lang;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    public function getBlock(): Block
    {
        return $this->block;
    }

    public function setBlock(Block $block): self
    {
        $this->block = $block;

        return $this;
    }

    public function getLang(): Lang
    {
        return $this->lang;
    }

    public function setLang(Lang $lang): self
    {
        $this->lang = $lang;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
