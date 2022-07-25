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

use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Command\EditContentBlockCommand;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Exception\CannotUpdateContentBlockException;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Exception\ContentBlockException;
use PrestaShopException;

/**
 * Class EditContentBlockHandler is responsible for editing content block data.
 *
 * @internal
 */
final class EditContentBlockHandler extends AbstractContentBlockCommandHandler
{
    /**
     * @throws ContentBlockException
     */
    public function handle(EditContentBlockCommand $command): void
    {
        try {
            $contentBlock = $this->getContentBlockEntity(
                $command->getContentBlockId()->getValue()
            );

            $oldHookId = $contentBlock->getHookId();
            $newHookId = $command->getHookId();

            if (null !== $newHookId) {
                $contentBlock->setHookId($newHookId);

                if ($oldHookId !== $newHookId) {
                    $contentBlock->setPosition($this->entityRepository->findMaxPosition($newHookId) + 1);
                }
            }

            $localizedNames = $command->getLocalizedNames();
            $localizedContents = $command->getLocalizedContents();

            if (null !== $localizedNames && null !== $localizedContents) {
                foreach ($contentBlock->getContentBlockLangs() as $contentBlockLangs) {
                    $contentBlock->removeContentBlockLang($contentBlockLangs);

                    $this->entityManager->remove($contentBlockLangs);
                }

                $this->entityManager->flush();

                $contentBlockLangs = $this->createContentBlockLangs($localizedNames, $localizedContents);
                foreach ($contentBlockLangs as $contentBlockLang) {
                    $contentBlock->addContentBlockLang($contentBlockLang);

                    $this->entityManager->persist($contentBlockLang);
                }
            }

            $this->entityManager->persist($contentBlock);
            $this->entityManager->flush();
        } catch (PrestaShopException $exception) {
            throw new CannotUpdateContentBlockException('An unexpected error occurred when editing content block', 0, $exception);
        }
    }
}
