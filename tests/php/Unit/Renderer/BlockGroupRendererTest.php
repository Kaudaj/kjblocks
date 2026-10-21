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

namespace Kaudaj\Module\Blocks\Tests\Unit\Renderer;

use Kaudaj\Module\Blocks\BlockContext;
use Kaudaj\Module\Blocks\BlockInterface;
use Kaudaj\Module\Blocks\BlockTypeProvider;
use Kaudaj\Module\Blocks\Entity\Block;
use Kaudaj\Module\Blocks\Entity\BlockGroup;
use Kaudaj\Module\Blocks\Entity\BlockGroupBlock;
use Kaudaj\Module\Blocks\Entity\BlockShop;
use Kaudaj\Module\Blocks\Renderer\BlockGroupRenderer;
use PHPUnit\Framework\TestCase;

/**
 * A block that cannot be rendered must not take the rest of the hook with it:
 * on production, a block of a type whose options had been emptied threw, and
 * the whole displayHome of a shop, banner included, came out blank.
 */
class BlockGroupRendererTest extends TestCase
{
    /**
     * @var array<int, array{0: Block, 1: \Throwable}>
     */
    private $failures = [];

    protected function setUp(): void
    {
        $this->failures = [];
    }

    public function testRendersTheBlocksInPositionOrder(): void
    {
        $group = $this->group([
            [3, 'text', 2, true, 'B'],
            [4, 'text', 1, true, 'A'],
        ]);

        self::assertSame(
            '<div class="block-group block-group-9">AB</div>',
            $this->renderer($group)->render($group)
        );
        self::assertSame([], $this->failures);
    }

    public function testABlockThatThrowsIsSkippedAndTheOthersStillRender(): void
    {
        $group = $this->group([
            [1, 'text', 1, true, 'banner'],
            [2, 'category_products_list', 2, true, new \RuntimeException('Missing required option "category".')],
            [3, 'text', 3, true, 'after'],
        ]);

        self::assertSame(
            '<div class="block-group block-group-9">bannerafter</div>',
            $this->renderer($group)->render($group)
        );

        self::assertCount(1, $this->failures);
        self::assertSame(2, $this->failures[0][0]->getId(), 'the failure names the block');
        self::assertSame('Missing required option "category".', $this->failures[0][1]->getMessage());
    }

    public function testAnErrorIsIsolatedLikeAnException(): void
    {
        $group = $this->group([
            [1, 'text', 1, true, 'first'],
            [2, 'text', 2, true, new \TypeError('wrong type')],
            [3, 'text', 3, true, 'last'],
        ]);

        self::assertSame(
            '<div class="block-group block-group-9">firstlast</div>',
            $this->renderer($group)->render($group)
        );
        self::assertCount(1, $this->failures);
        self::assertInstanceOf(\TypeError::class, $this->failures[0][1]);
    }

    public function testABlockWhoseTypeCannotBeBuiltIsSkippedToo(): void
    {
        // The options are checked when the provider builds the block type:
        // that is where an emptied "category" option throws.
        $group = $this->group([
            [1, 'text', 1, true, 'banner'],
            [2, 'category_products_list', 2, true, 'never rendered'],
        ]);
        $failing = new \RuntimeException('Missing required option "category".');

        self::assertSame(
            '<div class="block-group block-group-9">banner</div>',
            $this->renderer($group, [2 => $failing])->render($group)
        );
        self::assertCount(1, $this->failures);
        self::assertSame($failing, $this->failures[0][1]);
    }

    public function testInactiveBlocksAreLeftOutWithoutAFailure(): void
    {
        $group = $this->group([
            [1, 'text', 1, true, 'on'],
            [2, 'text', 2, false, 'off'],
        ]);

        self::assertSame(
            '<div class="block-group block-group-9">on</div>',
            $this->renderer($group)->render($group)
        );
        self::assertSame([], $this->failures);
    }

    public function testEveryFailingBlockIsReportedOnce(): void
    {
        $group = $this->group([
            [1, 'text', 1, true, new \RuntimeException('one')],
            [2, 'text', 2, true, new \LogicException('two')],
            [3, 'text', 3, true, 'ok'],
        ]);

        self::assertSame('<div class="block-group block-group-9">ok</div>', $this->renderer($group)->render($group));
        self::assertSame(['one', 'two'], array_map(static function (array $failure): string {
            return $failure[1]->getMessage();
        }, $this->failures));
    }

    public function testAReporterThatThrowsDoesNotTakeThePageDown(): void
    {
        $group = $this->group([
            [1, 'text', 1, true, new \RuntimeException('block')],
            [2, 'text', 2, true, 'still here'],
        ]);

        $renderer = new BlockGroupRenderer(
            $this->provider($group, []),
            $this->context($group),
            static function (): void {
                throw new \RuntimeException('the log is down');
            }
        );

        self::assertSame('<div class="block-group block-group-9">still here</div>', $renderer->render($group));
    }

    /**
     * @param array<int, \Throwable> $buildFailures block id => what the provider throws for it
     */
    private function renderer(BlockGroup $group, array $buildFailures = []): BlockGroupRenderer
    {
        return new BlockGroupRenderer(
            $this->provider($group, $buildFailures),
            $this->context($group),
            function (Block $block, \Throwable $e): void {
                $this->failures[] = [$block, $e];
            }
        );
    }

    /**
     * @param array<int, \Throwable> $buildFailures
     */
    private function provider(BlockGroup $group, array $buildFailures): BlockTypeProvider
    {
        $provider = $this->createMock(BlockTypeProvider::class);
        $provider->method('getBlockTypeFromEntity')->willReturnCallback(
            function (Block $block) use ($buildFailures): BlockInterface {
                if (isset($buildFailures[$block->getId()])) {
                    throw $buildFailures[$block->getId()];
                }

                $outcome = $this->outcomes[$block->getId()];
                $type = $this->createMock(BlockInterface::class);
                $type->method('render')->willReturnCallback(static function () use ($outcome): string {
                    if ($outcome instanceof \Throwable) {
                        throw $outcome;
                    }

                    return $outcome;
                });

                return $type;
            }
        );

        return $provider;
    }

    private function context(BlockGroup $group): BlockContext
    {
        $context = $this->createMock(BlockContext::class);
        $context->method('getBlockShop')->willReturnCallback(static function (Block $block): ?BlockShop {
            return $block->getBlockShop(null, null);
        });

        return $context;
    }

    /**
     * @var array<int, string|\Throwable>
     */
    private $outcomes = [];

    /**
     * @param array<int, array{0: int, 1: string, 2: int, 3: bool, 4: string|\Throwable}> $rows id, type, position, active, what render() gives
     */
    private function group(array $rows): BlockGroup
    {
        $this->outcomes = [];
        $group = new BlockGroup();
        $this->setId($group, 9);

        foreach ($rows as [$id, $type, $position, $active, $outcome]) {
            $block = (new Block())->setType($type);
            $this->setId($block, $id);
            $block->addBlockShop((new BlockShop())->setActive($active));
            $this->outcomes[$id] = $outcome;

            $group->addBlockGroupBlock(
                (new BlockGroupBlock())->setBlock($block)->setPosition($position)
            );
        }

        return $group;
    }

    private function setId(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
}
