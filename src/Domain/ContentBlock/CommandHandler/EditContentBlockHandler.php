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
use Kaudaj\Module\ContentBlocks\Entity\ContentBlock;
use Kaudaj\Module\ContentBlocks\Entity\ContentBlockHook;
use PrestaShop\PrestaShop\Core\Domain\Hook\ValueObject\HookId;
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

            $hooksIds = $command->getHooksIds();
            if ($hooksIds) {
                $hooksIds = array_map(function (HookId $hookId): int {
                    return $hookId->getValue();
                }, $hooksIds);

                $this->updateHooks($contentBlock, $hooksIds);
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

    /**
     * @param int[] $hooksIds
     */
    protected function updateHooks(ContentBlock $contentBlock, array $hooksIds): void
    {
        foreach ($hooksIds as $hookId) {
            if ($contentBlock->getContentBlockHook($hookId)) {
                continue;
            }

            $contentBlockHook = new ContentBlockHook();

            $contentBlockHook->setHookId($hookId);
            $contentBlockHook->setPosition($this->contentBlockHookRepository->findMaxPosition($hookId) + 1);

            $contentBlock->addContentBlockHook($contentBlockHook);
        }

        foreach ($contentBlock->getContentBlockHooks() as $contentBlockHook) {
            $hookId = $contentBlockHook->getHookId();
            if (in_array($hookId, $hooksIds)) {
                continue;
            }

            $contentBlock->removeContentBlockHook($contentBlockHook);
            $this->entityManager->remove($contentBlockHook);

            $this->contentBlockHookRepository->cleanPositions($hookId);
        }
    }
}
