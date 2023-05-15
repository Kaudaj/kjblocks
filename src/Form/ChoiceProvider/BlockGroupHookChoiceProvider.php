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

use Hook;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Query\GetBlockGroups;
use Kaudaj\Module\Blocks\Entity\Block;
use Kaudaj\Module\Blocks\Entity\BlockGroup;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Form\FormChoiceProviderInterface;

/**
 * Class BlockGroupHookChoiceProvider provides hook choices from actual block groups.
 */
final class BlockGroupHookChoiceProvider implements FormChoiceProviderInterface
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    public function __construct(CommandBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, int>
     */
    public function getChoices(array $options = [])
    {
        /** @var BlockGroup[] */
        $blockGroups = $this->commandBus->handle(new GetBlockGroups());

        $choices = [];
        foreach ($blockGroups as $blockGroup) {
            foreach ($blockGroup->getBlockGroupHooks() as $blockGroupHook) {
                $hookId = $blockGroupHook->getHookId();
                $hookName = (string) \Hook::getNameById($hookId);

                $choices[$hookName] = $hookId;
            }
        }

        return $choices;
    }
}
