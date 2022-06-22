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

namespace Kaudaj\Module\ContentBlocks\Domain\ContentBlock\CommandHandler;

use Exception;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Command\DeleteContentBlockCommand;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Exception\CannotDeleteContentBlockException;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Exception\ContentBlockException;

/**
 * Class DeleteContentBlockHandler is responsible for deleting content block data.
 *
 * @internal
 */
final class DeleteContentBlockHandler extends AbstractContentBlockCommandHandler
{
    /**
     * @throws ContentBlockException
     */
    public function handle(DeleteContentBlockCommand $command): void
    {
        $contentBlock = $this->getContentBlockEntity(
            $command->getContentBlockId()->getValue()
        );

        try {
            $this->entityManager->remove($contentBlock);
            $this->entityManager->flush();
        } catch (Exception $exception) {
            throw new CannotDeleteContentBlockException('An unexpected error occurred when deleting content block', 0, $exception);
        }
    }
}
