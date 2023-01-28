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

use Kaudaj\Module\Blocks\BlockFormMapperInterface;
use Kaudaj\Module\Blocks\BlockTypeProvider;
use Kaudaj\Module\Blocks\Form\Type\BlockTypeType;
use PrestaShop\PrestaShop\Adapter\ContainerFinder;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class BlockTypeFormDataHandler
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var BlockTypeProvider
     */
    private $blockTypeProvider;

    public function __construct(LegacyContext $legacyContext, BlockTypeProvider $blockTypeProvider)
    {
        $containerFinder = new ContainerFinder($legacyContext->getContext());
        $this->container = $containerFinder->getContainer();

        $this->blockTypeProvider = $blockTypeProvider;
    }

    /**
     * @param array<string, mixed> $formOptions
     */
    public function buildBlockOptions(int $blockId, string $formName, string $typeFieldName, string $type, array $formOptions): ?string
    {
        $block = $this->blockTypeProvider->getBlockType($type);

        if (!$block) {
            return null;
        }

        /** @var BlockFormMapperInterface */
        $blockFormHandler = $this->container->get($block->getFormMapper());

        // Filter old options from previous block type

        /** @var RequestStack */
        $requestStack = $this->container->get('request_stack');
        $currentRequest = $requestStack->getCurrentRequest();

        if ($currentRequest !== null) {
            $requestParam = $currentRequest->request->get($formName) ?: [];
            $filesParam = $currentRequest->files->get($formName) ?: [];

            $optionsInParam = function ($param) use ($typeFieldName): ?array {
                if (is_array($param) && key_exists($typeFieldName, $param)
                    && is_array($param[$typeFieldName]) && key_exists(BlockTypeType::FIELD_OPTIONS, $param[$typeFieldName])) {
                    return $param[$typeFieldName][BlockTypeType::FIELD_OPTIONS];
                }

                return null;
            };

            $requestOptions = $optionsInParam($requestParam) ?: [];
            $requestOptions = array_merge($requestOptions, $optionsInParam($filesParam) ?: []);

            $formOptions = array_intersect_key($formOptions, $requestOptions);
        }

        $formOptions = $this->array_filter_recursive($formOptions);

        return json_encode($blockFormHandler->mapToBlockOptions($blockId, $formOptions)) ?: null;
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
