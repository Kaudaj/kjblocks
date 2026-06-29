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

namespace Kaudaj\Module\Blocks\Tests\Unit\Domain\ValueObject;

use Kaudaj\Module\Blocks\Domain\ValueObject\Json;
use PHPUnit\Framework\TestCase;
use PrestaShop\PrestaShop\Core\Domain\Exception\DomainConstraintException;

/**
 * Covers the pure-logic core of the module's main feature. A kjblocks block is a
 * "type + options" pair; its options are serialised as a JSON string (column
 * `options` of kj_blocks_block_shop, carried by AddBlockCommand). The Json value
 * object is the guard that only a valid JSON string can ever describe a block's
 * options: it accepts a well-formed payload and stores it verbatim, and rejects a
 * malformed one with a DomainConstraintException.
 *
 * Pure logic, no database. Skips if the PrestaShop core (DomainConstraintException)
 * cannot be autoloaded.
 */
class JsonTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(DomainConstraintException::class)) {
            self::markTestSkipped('PrestaShop core unavailable (DomainConstraintException).');
        }
    }

    /**
     * @dataProvider validJsonProvider
     */
    public function testItAcceptsAndStoresValidJsonVerbatim(string $json): void
    {
        $value = new Json($json);

        // The value object keeps the exact string it was given (it is what gets
        // persisted as the block options), it does not re-encode it.
        self::assertSame($json, $value->getValue());
    }

    /**
     * @return array<string, array{0:string}>
     */
    public function validJsonProvider(): array
    {
        return [
            'block options object' => ['{"text":"<p>Hello</p>","width":12}'],
            'empty object' => ['{}'],
            'array' => ['[1,2,3]'],
            'json null literal' => ['null'],
            'quoted scalar' => ['"text"'],
        ];
    }

    /**
     * @dataProvider invalidJsonProvider
     */
    public function testItRejectsMalformedJson(string $json): void
    {
        $this->expectException(DomainConstraintException::class);

        new Json($json);
    }

    /**
     * @return array<string, array{0:string}>
     */
    public function invalidJsonProvider(): array
    {
        return [
            'unclosed object' => ['{"text":'],
            'trailing garbage' => ['{}garbage'],
            'bare word' => ['not-json'],
            'empty string' => [''],
        ];
    }
}
