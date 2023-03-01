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

namespace Kaudaj\Module\Blocks\Form\ChoiceProvider;

use Kaudaj\Module\Blocks\Domain\Block\Query\GetBlocks;
use Kaudaj\Module\Blocks\Entity\Block;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Form\FormChoiceProviderInterface;

/**
 * Class BlockGroupBlockChoiceProvider provides currently associated groups choices.
 */
final class BlockGroupBlockChoiceProvider implements FormChoiceProviderInterface
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @var int
     */
    private $contextLangId;

    public function __construct(CommandBusInterface $commandBus, int $contextLangId)
    {
        $this->commandBus = $commandBus;
        $this->contextLangId = $contextLangId;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, int>
     */
    public function getChoices(array $options = [])
    {
        /** @var Block[] */
        $blocks = $this->commandBus->handle(new GetBlocks());

        $choices = [];
        foreach ($blocks as $block) {
            foreach ($block->getBlockGroups() as $blockGroupBlock) {
                $blockGroup = $blockGroupBlock->getBlockGroup();

                if (in_array($blockGroup->getId(), $choices)) {
                    continue;
                }

                $blockGroupLang = $blockGroup->getBlockGroupLang($this->contextLangId);
                if ($blockGroupLang === null) {
                    continue;
                }

                $choices[$blockGroupLang->getName()] = $blockGroup->getId();
            }
        }

        return $choices;
    }
}
