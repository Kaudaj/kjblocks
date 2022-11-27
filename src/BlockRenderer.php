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

        foreach ($options as $option => &$value) {
            $option = strval($option);

            if (!(in_array($option, $multiLangOptions) && is_array($value))) {
                continue;
            }

            if (key_exists(0, $value)) {
                foreach ($value as $langValues) {
                    if (!is_array($langValues)) {
                        continue;
                    }

                    $value = $this->getContextLangValue($langValues, $defaultLangId);
                }
            } else {
                $value = $this->getContextLangValue($value, $defaultLangId);
            }
        }

        $resolver = new OptionsResolver();
        $block->configureOptions($resolver);
        $options = $resolver->resolve($options);

        return $block->render($options);
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
     * @param array<int, mixed> $langValues
     *
     * @return mixed
     */
    private function getContextLangValue(array $langValues, int $defaultLangId)
    {
        if (key_exists($this->contextLangId, $langValues)) {
            return $langValues[$this->contextLangId];
        } elseif (key_exists($defaultLangId, $langValues)) {
            return $langValues[$defaultLangId];
        }

        return null;
    }
}
