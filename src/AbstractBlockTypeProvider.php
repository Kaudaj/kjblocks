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

namespace Kaudaj\Module\Blocks;

use Kaudaj\Module\Blocks\Entity\Block;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\ContainerFinder;
use PrestaShop\PrestaShop\Adapter\Debug\DebugMode;
use PrestaShop\PrestaShop\Core\Exception\ContainerNotFoundException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validation;

/**
 * @template T of BlockInterface
 */
abstract class AbstractBlockTypeProvider
{
    /**
     * @var array<string, array<string, T>> [module name => [block name => block instance]]
     */
    protected $blockTypes = null;

    /**
     * @var array<int, T> [block entity id => block instance]
     */
    protected $blocks = [];

    /**
     * @var string
     */
    protected $hookName;

    /**
     * @var int
     */
    protected $contextLangId;

    public function __construct(int $contextLangId, string $hookName)
    {
        $this->contextLangId = $contextLangId;
        $this->hookName = $hookName;
    }

    /**
     * @return array<string, array<string, T>> [module name => [block name => block instance]]
     */
    public function getBlockTypes(): array
    {
        if ($this->blockTypes !== null) {
            return $this->blockTypes;
        }

        $container = $this->getContainer();
        if ($container === null) {
            throw new \RuntimeException("Can't retrieve container");
        }

        $blocks = \Hook::exec($this->hookName, [], null, true);
        $blocks = is_array($blocks) ? $blocks : [];

        $isDebugModeEnabled = (new DebugMode())->isDebugModeEnabled();

        $validator = Validation::createValidator();

        $this->blockTypes = [];
        foreach ($blocks as $moduleName => $moduleBlocks) {
            $this->blockTypes[$moduleName] = [];

            foreach ($moduleBlocks as $blockService) {
                try {
                    /** @var T|false */
                    $block = $container->get($blockService);
                } catch (\Exception $e) {
                    if ($isDebugModeEnabled) {
                        throw new \RuntimeException($e->getMessage());
                    }

                    continue;
                }

                if (!$block) {
                    throw new \RuntimeException('Container not available.');
                }

                $violations = $validator->validate($block, [
                    new Type(BlockInterface::class),
                ]);

                if (0 !== count($violations)) {
                    if ($isDebugModeEnabled) {
                        $message = 'Block ' . get_class($block) . ' is not valid:';

                        foreach ($violations as $violation) {
                            $message .= "- {$violation->getMessage()}\n";
                        }

                        throw new \RuntimeException($message);
                    }

                    continue;
                }

                $this->blockTypes[$moduleName][$block->getName()] = $block;
            }
        }

        return $this->blockTypes;
    }

    private function getContainer(): ?ContainerInterface
    {
        try {
            $context = \Context::getContext();
            if ($context === null) {
                return null;
            }

            $finder = new ContainerFinder($context);
            $container = $finder->getContainer();
        } catch (ContainerNotFoundException $e) {
            return null;
        }

        return $container;
    }

    /**
     * @param array<string, mixed>|null $options
     *
     * @return T
     */
    public function getBlockType(string $type, ?array $options = null)
    {
        $blockTypes = $this->getBlockTypes();

        $blockType = null;

        foreach ($blockTypes as $moduleBlocks) {
            if (key_exists($type, $moduleBlocks)) {
                $blockType = $moduleBlocks[$type];

                break;
            }
        }

        if ($blockType === null) {
            throw new \RuntimeException("Block type '$type' not found");
        }

        if ($options === null) {
            return $blockType;
        }

        $blockType = clone $blockType;
        $blockType->setOptions($this->configureOptions($blockType, $options));

        return $blockType;
    }

    /**
     * @param T $blockType
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function configureOptions($blockType, array $options = []): array
    {
        $this->setContextLangValue(
            $options,
            (new Configuration())->getInt('PS_LANG_DEFAULT'),
            $blockType->getMultiLangOptions()
        );

        $resolver = new OptionsResolver();
        $blockType->configureOptions($resolver);
        $options = $resolver->resolve($options);

        return $options;
    }

    /**
     * @param mixed[] $optionValue
     * @param string[] $multiLangOptions
     * @param string|int|null $option
     */
    private function setContextLangValue(array &$optionValue, int $defaultLangId, array $multiLangOptions, $option = null): void
    {
        if (empty($optionValue)) {
            return;
        }

        $isAssoc = count(array_filter(array_keys($optionValue), 'is_string')) > 0;

        if (($option === null || in_array($option, $multiLangOptions, true)) && !$isAssoc && !key_exists(0, $optionValue)) {
            if (key_exists($this->contextLangId, $optionValue)) {
                $optionValue = $optionValue[$this->contextLangId];
            } elseif (key_exists($defaultLangId, $optionValue)) {
                $optionValue = $optionValue[$defaultLangId];
            } else {
                $optionValue = null;
            }

            return;
        }

        foreach ($optionValue as $key => &$value) {
            if (!is_array($value)) {
                continue;
            }

            $this->setContextLangValue(
                $value,
                $defaultLangId,
                $multiLangOptions,
                in_array($key, $multiLangOptions, true) ? $key : $option
            );
        }
    }
}
