<?php
/**
 * Copyright since 2011 Prestarocket
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contact@prestarocket.com so we can send you a copy immediately.
 *
 * @author    Prestarocket <contact@prestarocket.com>
 * @copyright Since 2011 Prestarocket
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

namespace Kaudaj\Module\Blocks;

use Kaudaj\Module\Blocks\Entity\Block;
use PrestaShop\PrestaShop\Adapter\Configuration;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BlockRenderer
{
    public const HOOK_FILTER_RENDER = 'filterBlockRender';
    public const HOOK_PARAM_RENDER = 'render';

    /**
     * @var int
     */
    private $contextLangId;

    /**
     * @var BlockTypeProvider
     */
    private $blockTypeProvider;

    public function __construct(int $contextLangId, BlockTypeProvider $blockTypeProvider)
    {
        $this->contextLangId = $contextLangId;
        $this->blockTypeProvider = $blockTypeProvider;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return string
     */
    public function render(string $type, array $options = []): string
    {
        $block = $this->blockTypeProvider->getBlockType($type);

        if (!$block) {
            throw new \RuntimeException("Can't retrieve $type block");
        }

        $block = clone $block;

        if (!$options) {
            return $block->render();
        }

        $multiLangOptions = $block->getMultiLangOptions();
        $defaultLangId = (new Configuration())->getInt('PS_LANG_DEFAULT');

        $this->setContextLangValue($options, $defaultLangId, $multiLangOptions);

        $resolver = new OptionsResolver();
        $block->configureOptions($resolver);
        $options = $resolver->resolve($options);

        $blockRender = $block->render($options);
        $filteredBlockRender = strval(\Hook::exec(self::HOOK_FILTER_RENDER, [
            self::HOOK_PARAM_RENDER => $blockRender,
        ]));

        return $filteredBlockRender ?: $blockRender;
    }

    /**
     * @return string
     */
    public function renderBlock(Block $blockEntity): string
    {
        $blockType = $blockEntity->getType();

        if (!$blockEntity->getOptions()) {
            return $this->render($blockType);
        }

        $blockOptions = json_decode($blockEntity->getOptions(), true) ?: [];
        if (!is_array($blockOptions)) {
            return $this->render($blockType);
        }

        return $this->render($blockType, $blockOptions);
    }

    /**
     * @param mixed[] $optionValue
     * @param string[] $multiLangOptions
     */
    private function setContextLangValue(array &$optionValue, int $defaultLangId, array $multiLangOptions): void
    {
        $isAssoc = count(array_filter(array_keys($optionValue), 'is_string')) > 0;

        if (!$isAssoc && !key_exists(0, $optionValue)) {
            if (key_exists($this->contextLangId, $optionValue)) {
                $optionValue = $optionValue[$this->contextLangId];
            } elseif (key_exists($defaultLangId, $optionValue)) {
                $optionValue = $optionValue[$defaultLangId];
            } else {
                $optionValue = null;
            }

            return;
        }

        foreach ($optionValue as $option => &$value) {
            if (!is_array($value) || ($isAssoc && !in_array($option, $multiLangOptions))) {
                continue;
            }

            $this->setContextLangValue($value, $defaultLangId, $multiLangOptions);
        }
    }
}
