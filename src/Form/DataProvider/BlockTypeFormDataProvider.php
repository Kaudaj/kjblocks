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

namespace Kaudaj\Module\Blocks\Form\DataProvider;

use Kaudaj\Module\Blocks\BlockFormMapperInterface;
use Kaudaj\Module\Blocks\BlockTypeProvider;
use Kaudaj\Module\Blocks\Domain\ValueObject\Json;
use PrestaShop\PrestaShop\Adapter\ContainerFinder;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BlockTypeFormDataProvider
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var BlockTypeProvider
     */
    private $blockTypeProvider;

    /**
     * @param BlockTypeProvider $blockTypeProvider
     */
    public function __construct(LegacyContext $legacyContext, BlockTypeProvider $blockTypeProvider)
    {
        $containerFinder = new ContainerFinder($legacyContext->getContext());
        $this->container = $containerFinder->getContainer();

        $this->blockTypeProvider = $blockTypeProvider;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildFormOptions(?Json $optionsJson, int $blockId, string $type): array
    {
        if (!$optionsJson) {
            return [];
        }

        $options = json_decode($optionsJson->getValue(), true) ?: [];

        if (!$options || !is_array($options)) {
            return [];
        }

        $block = $this->blockTypeProvider->getBlockType($type, $options);

        /** @var BlockFormMapperInterface */
        $blockFormHandler = $this->container->get($block->getFormMapper());

        return $blockFormHandler->mapToFormData($blockId, $options);
    }
}
