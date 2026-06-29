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

namespace Kaudaj\Module\Blocks\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Kaudaj\Module\Blocks\Entity\Block;
use Kaudaj\Module\Blocks\Entity\BlockLang;
use Kaudaj\Module\Blocks\Entity\BlockShop;
use Kaudaj\Module\Blocks\Repository\BlockRepository;
use PHPUnit\Framework\TestCase;
use PrestaShopBundle\Entity\Lang;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Main feature, end-to-end at the PHP level: a kjblocks block is a "type +
 * localized name + options" record that the module persists and reads back
 * through its own Doctrine repository (BlockRepository). This test creates a
 * "text" block carrying JSON options via the module's own entities, flushes it to
 * the real bench database, reloads it from a cold EntityManager through
 * BlockRepository, and asserts the type, the localized name and the options
 * round-trip intact. The block it creates is its own and is removed in tearDown,
 * so the bench data is left untouched.
 *
 * Requires the PrestaShop legacy environment + Symfony kernel (Doctrine) booted by
 * bootstrap-integration.php; skips otherwise.
 */
class BlockPersistenceTest extends TestCase
{
    private const OPTIONS_JSON = '{"text":"<p>kjblocks integration test</p>"}';

    /** @var KernelInterface|null */
    private static $kernel;

    /** @var EntityManagerInterface|null */
    private $entityManager;

    /** @var int|null id of the block created by the test, for cleanup */
    private $createdBlockId;

    public static function setUpBeforeClass(): void
    {
        self::$kernel = self::bootKernel();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$kernel !== null) {
            self::$kernel->shutdown();
            self::$kernel = null;
        }
    }

    protected function setUp(): void
    {
        if (self::$kernel === null) {
            self::markTestSkipped('PrestaShop Symfony kernel / Doctrine not available.');
        }

        $em = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        if (!$em instanceof EntityManagerInterface) {
            self::markTestSkipped('Doctrine entity manager not available.');
        }

        $this->entityManager = $em;
    }

    protected function tearDown(): void
    {
        if ($this->entityManager === null || $this->createdBlockId === null) {
            return;
        }

        $this->entityManager->clear();

        $block = $this->getRepository()->find($this->createdBlockId);
        if ($block !== null) {
            $this->entityManager->remove($block);
            $this->entityManager->flush();
        }

        $this->createdBlockId = null;
    }

    public function testItPersistsAndReadsBackABlockThroughItsRepository(): void
    {
        $em = $this->entityManager;
        self::assertInstanceOf(EntityManagerInterface::class, $em);

        $lang = $em->getRepository(Lang::class)->findOneBy(['active' => true]);
        if (!$lang instanceof Lang) {
            self::markTestSkipped('No active language available on the shop.');
        }
        $langId = $lang->getId();

        // Build a "text" block (type + localized name + JSON options) with the
        // module's own entities and persist it through Doctrine.
        $block = new Block();
        $block->setType('text');

        $blockLang = new BlockLang();
        $blockLang->setLang($lang);
        $blockLang->setName('kjblocks IT block');
        $block->addBlockLang($blockLang);

        $blockShop = new BlockShop();
        $blockShop->setOptions(self::OPTIONS_JSON);
        $block->addBlockShop($blockShop);

        $em->persist($block);
        $em->flush();

        $this->createdBlockId = $block->getId();
        self::assertGreaterThan(0, $this->createdBlockId, 'block got a generated id');

        // Read it back from a cold identity map through the module's repository.
        $em->clear();

        $reloaded = $this->getRepository()->find($this->createdBlockId);
        self::assertInstanceOf(Block::class, $reloaded, 'block is found again via BlockRepository');
        self::assertSame('text', $reloaded->getType(), 'type round-trips');

        $reloadedLang = $reloaded->getBlockLang($langId);
        self::assertInstanceOf(BlockLang::class, $reloadedLang, 'localized name row persisted');
        self::assertSame('kjblocks IT block', $reloadedLang->getName(), 'name round-trips');

        $shops = $reloaded->getBlockShops();
        self::assertGreaterThan(0, $shops->count(), 'a block-shop options row persisted');
        $firstShop = $shops->first();
        self::assertInstanceOf(BlockShop::class, $firstShop);
        self::assertSame(self::OPTIONS_JSON, $firstShop->getOptions(), 'JSON options round-trip intact');
    }

    /**
     * @return BlockRepository
     */
    private function getRepository()
    {
        $em = $this->entityManager;
        self::assertInstanceOf(EntityManagerInterface::class, $em);

        /** @var BlockRepository $repository */
        $repository = $em->getRepository(Block::class);

        return $repository;
    }

    /**
     * Boot the appropriate PrestaShop Symfony kernel: AdminKernel on PS9 (split
     * kernels), AppKernel on PS8. Returns null when no concrete kernel can be
     * booted (e.g. the legacy env was not loaded), so tests skip cleanly.
     */
    private static function bootKernel(): ?KernelInterface
    {
        $root = getenv('_PS_ROOT_DIR_');
        if (!is_string($root) || $root === '' || !is_file($root . '/config/config.inc.php')) {
            return null;
        }

        $candidates = [
            'AdminKernel' => $root . '/app/AdminKernel.php',
            'AppKernel' => $root . '/app/AppKernel.php',
        ];

        foreach ($candidates as $class => $file) {
            if (!class_exists($class) && is_file($file)) {
                require_once $file;
            }

            if (!class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            if ($reflection->isAbstract()) {
                continue;
            }

            try {
                /** @var KernelInterface $kernel */
                $kernel = $reflection->newInstance('dev', true);
                $kernel->boot();

                return $kernel;
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }
}
