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

namespace Kaudaj\Module\Blocks\Domain\Block\QueryHandler;

use Doctrine\ORM\EntityManager;
use Kaudaj\Module\Blocks\BlockInterface;
use Kaudaj\Module\Blocks\Domain\Block\Exception\BlockNotFoundException;
use Kaudaj\Module\Blocks\Domain\Block\Query\GetAvailableBlocksTypes;
use PrestaShop\PrestaShop\Adapter\ContainerFinder;
use PrestaShop\PrestaShop\Adapter\Debug\DebugMode;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Core\Exception\ContainerNotFoundException;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validation;

final class GetAvailableBlocksTypesHandler extends AbstractBlockQueryHandler
{
    public const HOOK_EXTRA_BLOCKS = 'actionGetExtraBlocks';
    public const HOOK_PARAM_BLOCKS_SERVICES = 'blocks_services';

    /**
     * @var LegacyContext
     */
    private $legacyContext;

    public function __construct(EntityManager $entityManager, LegacyContext $legacyContext)
    {
        parent::__construct($entityManager);

        $this->legacyContext = $legacyContext;
    }

    /**
     * @return array<string, BlockInterface>
     *
     * @throws \PrestaShopException
     * @throws BlockNotFoundException
     */
    public function handle(GetAvailableBlocksTypes $query): array
    {
        try {
            $finder = new ContainerFinder($this->legacyContext->getContext());
            $container = $finder->getContainer();
        } catch (ContainerNotFoundException $e) {
            return [];
        }

        $blocksServices = [
            'kaudaj.module.blocks.block.container',
            'kaudaj.module.blocks.block.text',
        ];

        \Hook::exec(self::HOOK_EXTRA_BLOCKS, [
            self::HOOK_PARAM_BLOCKS_SERVICES => &$blocksServices,
        ]);

        $isDebugModeEnabled = (new DebugMode())->isDebugModeEnabled();

        $blocks = [];
        foreach ($blocksServices as $blockService) {
            try {
                /** @var BlockInterface|false */
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

            $validator = Validation::createValidator();
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

            $blocks[$block->getName()] = $block;
        }

        return $blocks;
    }
}
