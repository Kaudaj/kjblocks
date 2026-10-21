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

namespace Kaudaj\Module\Blocks\Form\DataHandler;

use Kaudaj\Module\Blocks\AbstractBlockTypeProvider;
use Kaudaj\Module\Blocks\BlockContext;
use Kaudaj\Module\Blocks\BlockFormMapperInterface;
use Kaudaj\Module\Blocks\Domain\Block\Query\GetBlock;
use Kaudaj\Module\Blocks\Entity\Block;
use Kaudaj\Module\Blocks\Form\Type\BlockTypeType;
use PrestaShop\PrestaShop\Adapter\ContainerFinder;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Adapter\Shop\Context as ShopContext;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShopBundle\Service\Form\MultistoreCheckboxEnabler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class BlockTypeFormDataHandler
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var AbstractBlockTypeProvider<\Kaudaj\Module\Blocks\BlockInterface>
     */
    private $blockTypeProvider;

    /**
     * @var ShopContext
     */
    private $shopContext;

    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @var BlockContext
     */
    private $blockContext;

    /**
     * @var BlockOptionsMerger
     */
    private $optionsMerger;

    /**
     * @param AbstractBlockTypeProvider<\Kaudaj\Module\Blocks\BlockInterface> $blockTypeProvider
     */
    public function __construct(
        LegacyContext $legacyContext,
        AbstractBlockTypeProvider $blockTypeProvider,
        ShopContext $shopContext,
        CommandBusInterface $commandBus,
        BlockContext $blockContext,
        ?BlockOptionsMerger $optionsMerger = null,
    ) {
        $context = $legacyContext->getContext();
        if ($context === null) {
            throw new \RuntimeException('Legacy context is not available.');
        }

        $containerFinder = new ContainerFinder($context);
        $this->container = $containerFinder->getContainer();

        $this->blockTypeProvider = $blockTypeProvider;
        $this->shopContext = $shopContext;
        $this->commandBus = $commandBus;
        $this->blockContext = $blockContext;
        $this->optionsMerger = $optionsMerger ?? new BlockOptionsMerger();
    }

    /**
     * @param array<string, mixed> $formOptions
     *
     * @return array<string, mixed>
     */
    public function buildBlockOptions(int $blockId, string $formName, string $typeFieldName, string $type, array $formOptions): array
    {
        $multistorePrefix = MultistoreCheckboxEnabler::MULTISTORE_FIELD_PREFIX;
        $isAllShopContext = $this->shopContext->isAllShopContext();

        // Options posted by the form, to tell the real fields from old options
        // of the block that travel through the form data.

        /** @var RequestStack */
        $requestStack = $this->container->get('request_stack');
        $currentRequest = $requestStack->getCurrentRequest();

        $requestOptions = null;
        if ($currentRequest !== null) {
            // Use all() rather than get(): on Symfony 6 (PS9) InputBag::get() throws a
            // BadRequestException when the value is non-scalar, and $formName holds the
            // whole submitted form array (e.g. "product_extra"). all() returns that array
            // (or [] when absent) without throwing.
            $requestParam = $currentRequest->request->all($formName);
            $filesParam = $currentRequest->files->all($formName);

            $optionsInParam = function ($param) use ($typeFieldName): array {
                if (is_array($param) && key_exists($typeFieldName, $param)
                    && is_array($param[$typeFieldName]) && key_exists(BlockTypeType::FIELD_OPTIONS, $param[$typeFieldName])) {
                    return $param[$typeFieldName][BlockTypeType::FIELD_OPTIONS];
                }

                return [];
            };

            $requestOptions = $optionsInParam($requestParam);
            $requestOptions = array_merge($requestOptions, $optionsInParam($filesParam));
        }

        // The options the form has a field for: the ones posted, and the ones
        // with a multistore box (a field left unchecked is disabled, so it is
        // not posted, but its box is there). The form data also carries the
        // block's old options, which have no field. In a shop or group context,
        // the fields whose box is unchecked are the ones that mean "use the
        // wider scope's value". See BlockOptionsMerger.
        $formOptionNames = [];
        $uncheckedOptionNames = [];
        foreach (array_keys($formOptions) as $key) {
            $key = (string) $key;

            if (strpos($key, $multistorePrefix) === 0) {
                $name = substr($key, strlen($multistorePrefix));
                $formOptionNames[] = $name;

                if (!$isAllShopContext && $formOptions[$key] !== true) {
                    $uncheckedOptionNames[] = $name;
                }

                continue;
            }

            if ($requestOptions === null || key_exists($key, $requestOptions)) {
                $formOptionNames[] = $key;
            }
        }
        $formOptionNames = array_values(array_unique($formOptionNames));

        if (!$isAllShopContext) {
            $formOptions = array_filter($formOptions, function ($key) use ($formOptions, $multistorePrefix) {
                if (strpos($key, $multistorePrefix) === 0) {
                    return true;
                }

                $multistoreKey = $multistorePrefix . $key;

                return !key_exists($multistoreKey, $formOptions) || $formOptions[$multistoreKey] === true;
            }, ARRAY_FILTER_USE_KEY);
        }

        $formOptions = array_filter($formOptions, function ($key) use ($multistorePrefix) {
            return strpos($key, $multistorePrefix) === false;
        }, ARRAY_FILTER_USE_KEY);

        $block = $this->blockTypeProvider->getBlockType($type);

        /** @var BlockFormMapperInterface */
        $blockFormHandler = $this->container->get($block->getFormMapper());

        // Filter old options from previous block type
        if ($requestOptions !== null) {
            $formOptions = array_intersect_key($formOptions, $requestOptions);
        }

        $formOptions = $this->array_filter_recursive($formOptions);

        $submittedOptions = $blockFormHandler->mapToBlockOptions($blockId, $formOptions);

        [$storedOptions, $inheritedOptions] = $this->getStoredOptions($blockId, $type);

        return $this->optionsMerger->merge($submittedOptions, $storedOptions, $inheritedOptions, $formOptionNames, $uncheckedOptionNames);
    }

    /**
     * The options stored on the row being edited, and the ones it falls back on
     * (see BlockOptionsMerger). Nothing is kept from a block of another type:
     * its options mean nothing to the new one.
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function getStoredOptions(int $blockId, string $type): array
    {
        /** @var Block $block */
        $block = $this->commandBus->handle(new GetBlock($blockId));

        if ($block->getType() !== $type) {
            return [[], []];
        }

        return [
            $this->blockContext->getOwnBlockOptions($block),
            $this->blockContext->getInheritedBlockOptions($block),
        ];
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function array_filter_recursive(array $input): array
    {
        foreach ($input as &$value) {
            if (is_array($value)) {
                $value = $this->array_filter_recursive($value);
            }
        }

        return array_filter($input);
    }
}
