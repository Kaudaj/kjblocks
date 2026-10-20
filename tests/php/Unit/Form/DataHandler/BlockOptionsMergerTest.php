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

namespace Kaudaj\Module\Blocks\Tests\Unit\Form\DataHandler;

use Kaudaj\Module\Blocks\Form\DataHandler\BlockOptionsMerger;
use PHPUnit\Framework\TestCase;

/**
 * What a save of the block form leaves on the row of the scope being edited.
 */
class BlockOptionsMergerTest extends TestCase
{
    private const FORM = ['width', 'height', 'classes', 'identifier', 'background_image'];

    /**
     * The incident: the form of a "category products list" block has the five
     * container fields and nothing else; saved in a shop context with no box
     * checked, it wrote "[]" and the block lost its category.
     */
    public function testOptionsTheFormHasNoFieldForSurviveAnEmptySave(): void
    {
        $stored = [
            'title' => ['1' => 'Produits phares', '2' => 'Featured products'],
            'category' => 8474,
            'limit' => 8,
            'sort_by' => 'position',
            'sort_way' => 'asc',
        ];

        self::assertSame(
            $stored,
            (new BlockOptionsMerger())->merge([], $stored, [], self::FORM, self::FORM)
        );
    }

    public function testACheckedFieldIsWrittenNextToTheOptionsTheFormCannotShow(): void
    {
        $merged = (new BlockOptionsMerger())->merge(
            ['classes' => 'wide'],
            ['category' => 8474, 'limit' => 8],
            [],
            self::FORM,
            ['width', 'height', 'identifier', 'background_image']
        );

        self::assertSame(['category' => 8474, 'limit' => 8, 'classes' => 'wide'], $merged);
    }

    public function testASubmittedValueReplacesTheStoredOne(): void
    {
        $merged = (new BlockOptionsMerger())->merge(
            ['classes' => 'new'],
            ['classes' => 'old', 'category' => 3],
            [],
            self::FORM,
            []
        );

        self::assertSame(['category' => 3, 'classes' => 'new'], $merged);
    }

    public function testAFieldEmptiedInTheFormIsDropped(): void
    {
        // Checked (not in the unchecked list) but empty: array_filter removed
        // it from the submitted options, and the stored value must not come back.
        $merged = (new BlockOptionsMerger())->merge(
            [],
            ['classes' => 'old', 'category' => 3],
            [],
            self::FORM,
            []
        );

        self::assertSame(['category' => 3], $merged);
    }

    public function testAnUncheckedFieldGoesBackToTheWiderScopeValue(): void
    {
        // The all-shops row has "classes": unchecking it on the shop means "use
        // that one", so the shop's own value is dropped.
        $merged = (new BlockOptionsMerger())->merge(
            [],
            ['classes' => 'shop-only', 'category' => 3],
            ['classes' => 'all-shops'],
            self::FORM,
            ['classes']
        );

        self::assertSame(['category' => 3], $merged);
    }

    public function testAnUncheckedFieldKeepsItsValueWhenNoWiderScopeHasOne(): void
    {
        // Nothing to fall back on: dropping it would only lose the value.
        $merged = (new BlockOptionsMerger())->merge(
            [],
            ['classes' => 'shop-only', 'category' => 3],
            ['width' => 100],
            self::FORM,
            ['classes', 'width']
        );

        self::assertSame(['category' => 3, 'classes' => 'shop-only'], $merged);
    }

    public function testAnUncheckedFieldTheRowNeverHadStaysAbsent(): void
    {
        $merged = (new BlockOptionsMerger())->merge([], ['category' => 3], [], self::FORM, self::FORM);

        self::assertSame(['category' => 3], $merged);
    }

    public function testNothingStoredMeansTheSubmittedOptionsAlone(): void
    {
        self::assertSame(
            ['classes' => 'a'],
            (new BlockOptionsMerger())->merge(['classes' => 'a'], [], [], self::FORM, [])
        );
        self::assertSame([], (new BlockOptionsMerger())->merge([], [], [], self::FORM, self::FORM));
    }
}
