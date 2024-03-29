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

namespace Kaudaj\Module\Blocks\Form\Mapper;

use Kaudaj\Module\Blocks\Block\ContainerBlock;
use Kaudaj\Module\Blocks\BlockFormMapper;
use Kaudaj\Module\Blocks\File\BlockFileManager;
use Kaudaj\Module\Blocks\Form\Type\Block\ContainerBlockType;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ContainerBlockFormMapper extends BlockFormMapper
{
    public const BACKGROUND_FILENAME = 'background';

    /**
     * @var BlockFileManager
     */
    private $blockFileManager;

    public function __construct(BlockFileManager $blockFileManager)
    {
        $this->blockFileManager = $blockFileManager;
    }

    public function mapToBlockOptions(int $blockId, array $formData): array
    {
        $blockOptions = parent::mapToBlockOptions($blockId, $formData);

        if (!key_exists(ContainerBlock::OPTION_BACKGROUND_IMAGE, $blockOptions)
            || !($blockOptions[ContainerBlock::OPTION_BACKGROUND_IMAGE] instanceof UploadedFile)) {
            $existingBackgroundUrl = $this->blockFileManager->getUrl($blockId, self::BACKGROUND_FILENAME);

            if ($existingBackgroundUrl === null) {
                return $blockOptions;
            }

            return $blockOptions + [ContainerBlock::OPTION_BACKGROUND_IMAGE => $existingBackgroundUrl];
        }

        /** @var UploadedFile */
        $backgroundImage = $blockOptions[ContainerBlock::OPTION_BACKGROUND_IMAGE];

        $destinationFilename = self::BACKGROUND_FILENAME . '.' . ($backgroundImage->guessExtension() ?: 'jpg');
        $this->blockFileManager->upload($blockId, $backgroundImage, $destinationFilename);

        $blockOptions[ContainerBlock::OPTION_BACKGROUND_IMAGE] = $this->blockFileManager->getUrl($blockId, self::BACKGROUND_FILENAME);

        return $blockOptions;
    }

    public function mapToFormData(int $blockId, array $blockOptions): array
    {
        $formData = parent::mapToFormData($blockId, $blockOptions);

        if (!key_exists(ContainerBlockType::FIELD_BACKGROUND_IMAGE, $formData)) {
            return $formData;
        }

        $filePathname = $this->blockFileManager->find($blockId, self::BACKGROUND_FILENAME);

        if ($filePathname === null) {
            return $formData;
        }

        $formData[ContainerBlockType::FIELD_BACKGROUND_IMAGE] = new File($filePathname);

        return $formData;
    }
}
