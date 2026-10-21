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

namespace Kaudaj\Module\Blocks\Tests\Unit\Form\DataHandler {
    use Kaudaj\Module\Blocks\BlockContext;
    use Kaudaj\Module\Blocks\BlockFormMapper;
    use Kaudaj\Module\Blocks\BlockInterface;
    use Kaudaj\Module\Blocks\BlockTypeProvider;
    use Kaudaj\Module\Blocks\Entity\Block;
    use Kaudaj\Module\Blocks\Entity\BlockShop;
    use Kaudaj\Module\Blocks\Form\DataHandler\BlockOptionsMerger;
    use Kaudaj\Module\Blocks\Form\DataHandler\BlockTypeFormDataHandler;
    use PHPUnit\Framework\TestCase;
    use PrestaShop\PrestaShop\Adapter\Shop\Context as ShopContext;
    use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
    use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;
    use PrestaShopBundle\Entity\Shop as ShopEntity;
    use Symfony\Component\DependencyInjection\ContainerInterface;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\RequestStack;

    /**
     * What the block form does to the options of the row it saves, run through
     * the real BlockTypeFormDataHandler::buildBlockOptions() and BlockContext:
     * only the container, the command bus, the request and the block entity are
     * replaced. The form of a "container" based block type has five fields
     * (width, height, classes, identifier, background_image); the other options
     * of the block (category, limit...) are not in it.
     */
    class BlockTypeFormDataHandlerTest extends TestCase
    {
        private const FIELDS = ['width', 'height', 'classes', 'identifier', 'background_image'];
        private const STORED = [
            'title' => ['1' => 'Produits phares', '2' => 'Featured products'],
            'category' => 8474,
            'limit' => 8,
            'sort_by' => 'position',
            'sort_way' => 'asc',
        ];

        protected function setUp(): void
        {
            if (!class_exists(ShopConstraint::class) || !class_exists(RequestStack::class) || !class_exists(ShopEntity::class)) {
                self::markTestSkipped('PS core classes unavailable (_PS_ROOT_DIR_ not set).');
            }
        }

        public function testASaveInAShopWithNoBoxCheckedKeepsTheOptionsTheFormHasNoFieldFor(): void
        {
            // The incident of the home page of shop 2: "Save" without touching a thing.
            $options = $this->build(self::STORED, [], 'category_products_list', 'category_products_list', [], 2);

            self::assertSame(self::STORED, $options);
        }

        public function testACheckedFieldIsAddedAndTheOthersAreKept(): void
        {
            $options = $this->build(self::STORED, [], 'category_products_list', 'category_products_list', ['classes' => 'wide'], 2);

            self::assertSame(self::STORED + ['classes' => 'wide'], $options);
        }

        public function testUncheckingAFieldTheAllShopsRowSetsLetsTheShopInherit(): void
        {
            $stored = self::STORED + ['classes' => 'shop-only'];

            $options = $this->build($stored, ['classes' => 'all-shops'], 'text', 'text', [], 2);

            self::assertSame(self::STORED, $options, 'the shop row no longer sets "classes"; the all-shops row still does');
        }

        public function testUncheckingAFieldNoWiderRowSetsKeepsItsValue(): void
        {
            $stored = self::STORED + ['classes' => 'shop-only'];

            $options = $this->build($stored, [], 'text', 'text', [], 2);

            self::assertSame($stored, $options);
        }

        public function testInAllShopsTheSubmittedFieldsAreWrittenNextToTheStoredOnes(): void
        {
            $options = $this->build(self::STORED, [], 'text', 'text', ['classes' => 'wide'], null);

            self::assertSame(self::STORED + ['classes' => 'wide'], $options);
        }

        public function testInAllShopsAnEmptiedFieldIsRemoved(): void
        {
            $stored = self::STORED + ['classes' => 'old'];

            // The field is posted, empty.
            $options = $this->build($stored, [], 'text', 'text', ['classes' => ''], null);

            self::assertSame(self::STORED, $options);
        }

        public function testTheOptionsOfAnotherBlockTypeAreNotKept(): void
        {
            // The block was a category list and is being saved as another type.
            $options = $this->build(self::STORED, [], 'category_products_list', 'text', ['classes' => 'wide'], 2);

            self::assertSame(['classes' => 'wide'], $options);
        }

        public function testANewBlockHasNothingToKeep(): void
        {
            $options = $this->build([], [], 'text', 'text', ['classes' => 'wide'], 2);

            self::assertSame(['classes' => 'wide'], $options);
        }

        /**
         * @param array<string, mixed> $stored options on the row of the scope being edited
         * @param array<string, mixed> $allShops options on the all-shops row ([] : no such row)
         * @param array<string, string> $checked field => posted value, for the fields whose box is checked
         * @param int|null $shopId the shop whose context saves; null: all shops
         *
         * @return array<string, mixed>
         */
        private function build(array $stored, array $allShops, string $storedType, string $submittedType, array $checked, ?int $shopId): array
        {
            $block = (new Block())->setType($storedType);
            $id = new \ReflectionProperty($block, 'id');
            $id->setAccessible(true);
            $id->setValue($block, 54);

            $ownRow = (new BlockShop())->setOptions(json_encode($stored) ?: null);
            if ($shopId !== null) {
                $shop = new ShopEntity();
                $shopIdProperty = new \ReflectionProperty($shop, 'id');
                $shopIdProperty->setAccessible(true);
                $shopIdProperty->setValue($shop, $shopId);
                $ownRow->setShop($shop);
            }
            $block->addBlockShop($ownRow);
            if ($shopId !== null && $allShops !== []) {
                $block->addBlockShop((new BlockShop())->setOptions(json_encode($allShops) ?: null));
            }

            $constraint = $shopId !== null ? ShopConstraint::shop($shopId) : ShopConstraint::allShops();
            $shopContext = $this->createMock(ShopContext::class);
            $shopContext->method('getShopConstraint')->willReturn($constraint);
            $shopContext->method('isAllShopContext')->willReturn($shopId === null);

            // What Symfony hands the data handler: every field of the form, plus
            // (in a shop context) the state of its box. An unchecked box and
            // its disabled field are not posted.
            $formOptions = [];
            $posted = [];
            foreach (self::FIELDS as $field) {
                if ($shopId !== null) {
                    $formOptions['multistore_' . $field] = isset($checked[$field]);
                }
                $formOptions[$field] = $checked[$field] ?? $stored[$field] ?? null;
                if (isset($checked[$field]) || $shopId === null) {
                    $posted[$field] = $checked[$field] ?? '';
                }
                if (isset($checked[$field]) && $shopId !== null) {
                    $posted['multistore_' . $field] = '1';
                }
            }

            // The form data also carries, untouched, the stored options that
            // have no field (the data provider fills the whole options array).
            $formOptions += array_diff_key($stored, array_flip(self::FIELDS));

            $request = Request::create('/', 'POST', ['block' => ['type' => ['options' => $posted]]]);
            $requestStack = new RequestStack();
            $requestStack->push($request);

            $container = $this->createMock(ContainerInterface::class);
            $container->method('get')->willReturnCallback(static function (string $service) use ($requestStack) {
                return $service === 'request_stack' ? $requestStack : new BlockFormMapper();
            });

            $type = $this->createMock(BlockInterface::class);
            $type->method('getFormMapper')->willReturn('mapper');
            $provider = $this->createMock(BlockTypeProvider::class);
            $provider->method('getBlockType')->willReturn($type);

            $bus = $this->createMock(CommandBusInterface::class);
            $bus->method('handle')->willReturn($block);

            $handler = (new \ReflectionClass(BlockTypeFormDataHandler::class))->newInstanceWithoutConstructor();
            foreach ([
                'container' => $container,
                'blockTypeProvider' => $provider,
                'shopContext' => $shopContext,
                'commandBus' => $bus,
                'blockContext' => new BlockContext(1, $shopContext),
                'optionsMerger' => new BlockOptionsMerger(),
            ] as $property => $value) {
                $reflection = new \ReflectionProperty(BlockTypeFormDataHandler::class, $property);
                $reflection->setAccessible(true);
                $reflection->setValue($handler, $value);
            }

            return $handler->buildBlockOptions(54, 'block', 'type', $submittedType, $formOptions);
        }
    }
}

namespace {
    // The legacy class BlockContext asks for the group of a shop; the unit
    // tests have no legacy classes, and group 1 holds shop 2.
    if (!class_exists('Shop')) {
        class Shop
        {
            public static function getGroupFromShop(int $shopId): int
            {
                return 1;
            }
        }
    }
}
