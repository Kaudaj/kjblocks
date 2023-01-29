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

use PrestaShop\PrestaShop\Adapter\ContainerFinder;
use PrestaShop\PrestaShop\Adapter\Debug\DebugMode;
use PrestaShop\PrestaShop\Core\Exception\ContainerNotFoundException;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validation;

/**
 * @template T of BlockInterface
 */
class BlockTypeProvider
{
    /**
     * @var array<string, array<string, T>> [module name => [block name => block instance]]
     */
    protected static $blockTypes = null;

    /**
     * @var string
     */
    protected $hookName;

    public function __construct(string $hookName)
    {
        $this->hookName = $hookName;
    }

    /**
     * @return array<string, array<string, T>> [module name => [block name => block instance]]
     */
    public function getBlockTypes(): array
    {
        if (self::$blockTypes !== null) {
            return self::$blockTypes;
        }

        $container = $this->getContainer();
        if ($container === null) {
            throw new RuntimeException("Can't retrieve container");
        }

        $blocks = \Hook::exec($this->hookName, [], null, true);
        $blocks = is_array($blocks) ? $blocks : [];

        $isDebugModeEnabled = (new DebugMode())->isDebugModeEnabled();

        $validator = Validation::createValidator();

        self::$blockTypes = [];
        foreach ($blocks as $moduleName => $moduleBlocks) {
            self::$blockTypes[$moduleName] = [];

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

                self::$blockTypes[$moduleName][$block->getName()] = $block;
            }
        }

        return self::$blockTypes;
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
     * @return T|null
     */
    public function getBlockType(string $type): ?BlockInterface
    {
        $blockTypes = $this->getBlockTypes();

        foreach ($blockTypes as $moduleBlocks) {
            if (key_exists($type, $moduleBlocks)) {
                return $moduleBlocks[$type];
            }
        }

        return null;
    }
}
