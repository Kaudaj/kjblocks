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

namespace Kaudaj\Module\Blocks\Domain\BlockGroup\CommandHandler;

use Kaudaj\Module\Blocks\Domain\BlockGroup\Command\AddBlockGroupCommand;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Exception\BlockGroupException;
use Kaudaj\Module\Blocks\Domain\BlockGroup\Exception\CannotAddBlockGroupException;
use Kaudaj\Module\Blocks\Domain\BlockGroup\ValueObject\BlockGroupId;
use Kaudaj\Module\Blocks\Entity\BlockGroup;
use Kaudaj\Module\Blocks\Entity\BlockGroupHook;

final class AddBlockGroupHandler extends AbstractBlockGroupCommandHandler
{
    /**
     * @throws CannotAddBlockGroupException
     * @throws BlockGroupException
     */
    public function handle(AddBlockGroupCommand $command): BlockGroupId
    {
        try {
            $blockGroup = new BlockGroup();

            foreach ($command->getHooksIds() as $hookId) {
                $blockGroupHook = new BlockGroupHook();

                $hookId = $hookId->getValue();

                $blockGroupHook->setHookId($hookId);
                $blockGroupHook->setPosition($this->blockGroupHookRepository->findMaxPosition($hookId) + 1);

                $blockGroup->addBlockGroupHook($blockGroupHook);
            }

            $localizedNames = $command->getLocalizedNames();

            $configuratorBlockGroupLangs = $this->createBlockGroupLangs($localizedNames);
            foreach ($configuratorBlockGroupLangs as $configuratorBlockGroupLang) {
                $blockGroup->addBlockGroupLang($configuratorBlockGroupLang);
            }

            $this->entityManager->persist($blockGroup);
            $this->entityManager->flush();
        } catch (\PrestaShopException $exception) {
            throw new BlockGroupException('An unexpected error occurred when adding blockGroup', 0, $exception);
        }

        return new BlockGroupId((int) $blockGroup->getId());
    }
}
