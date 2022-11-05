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

use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Command\AddContentBlockCommand;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Exception\CannotAddContentBlockException;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Exception\ContentBlockException;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\ValueObject\ContentBlockId;
use Kaudaj\Module\ContentBlocks\Entity\ContentBlock;
use Kaudaj\Module\ContentBlocks\Entity\ContentBlockHook;
use PrestaShopException;

/**
 * Class AddContentBlockHandler is used for adding content block data.
 */
final class AddContentBlockHandler extends AbstractContentBlockCommandHandler
{
    /**
     * @throws CannotAddContentBlockException
     * @throws ContentBlockException
     */
    public function handle(AddContentBlockCommand $command): ContentBlockId
    {
        try {
            $contentBlock = new ContentBlock();

            foreach ($command->getHooksIds() as $hookId) {
                $contentBlockHook = new ContentBlockHook();

                $hookId = $hookId->getValue();

                $contentBlockHook->setHookId($hookId);
                $contentBlockHook->setPosition($this->contentBlockHookRepository->findMaxPosition($hookId) + 1);

                $contentBlock->addContentBlockHook($contentBlockHook);
            }

            $localizedNames = $command->getLocalizedNames();
            $localizedContents = $command->getLocalizedContents();

            $configuratorBlockLangs = $this->createContentBlockLangs($localizedNames, $localizedContents);
            foreach ($configuratorBlockLangs as $configuratorBlockLang) {
                $contentBlock->addContentBlockLang($configuratorBlockLang);
            }

            $this->entityManager->persist($contentBlock);
            $this->entityManager->flush();
        } catch (PrestaShopException $exception) {
            throw new ContentBlockException('An unexpected error occurred when adding content block', 0, $exception);
        }

        return new ContentBlockId((int) $contentBlock->getId());
    }
}
