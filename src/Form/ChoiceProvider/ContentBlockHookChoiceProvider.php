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

namespace Kaudaj\Module\ContentBlocks\Form\ChoiceProvider;

use Hook;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Query\GetContentBlocks;
use Kaudaj\Module\ContentBlocks\Entity\ContentBlock;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Form\FormChoiceProviderInterface;

/**
 * Class ContentBlockHookChoiceProvider provides hook choices from actual content blocks.
 */
final class ContentBlockHookChoiceProvider implements FormChoiceProviderInterface
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
        /** @var ContentBlock[] */
        $contentBlocks = $this->commandBus->handle(new GetContentBlocks());

        $choices = [];
        foreach ($contentBlocks as $contentBlock) {
            foreach ($contentBlock->getContentBlockHooks() as $contentBlockHook) {
                $hookId = $contentBlockHook->getHookId();
                $hookName = strval(Hook::getNameById($hookId));

                $choices[$hookName] = $hookId;
            }
        }

        return $choices;
    }
}
