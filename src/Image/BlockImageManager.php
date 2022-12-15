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

namespace Kaudaj\Module\Blocks\Image;

use Kaudaj\Module\Blocks\Image\Uploader\BlockImageUploader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class BlockImageManager
{
    /**
     * @var string
     */
    private $blockImageDir;

    public function __construct()
    {
        $this->blockImageDir = _PS_MODULE_DIR_ . 'kjblocks/views/img/blocks/';
    }

    public function upload(UploadedFile $image, int $blockId, string $name, ?int $langId = null): void
    {
        $this->delete($blockId, $name, $langId);

        $imageUploader = new BlockImageUploader();
        $destination = $this->blockImageDir . $this->getFilename($blockId, $name, $langId) . '.' . ($image->guessExtension() ?: 'jpg');

        $imageUploader->upload($image, $destination);
    }

    public function delete(int $blockId, ?string $name = null, ?int $langId = null): void
    {
        $filename = $this->getFilename($blockId, $name, $langId) . '.*';

        $finder = new Finder();
        $finder->files()->path($filename)->in($this->blockImageDir);

        $filesystem = new Filesystem();

        foreach ($finder as $file) {
            $filesystem->remove($file->getRealPath());
        }
    }

    public function getUrl(int $blockId, string $name, ?int $langId = null): ?string
    {
        $filePathname = $this->find($blockId, $name, $langId);
        $link = new \Link();

        if ($filePathname === null) {
            return null;
        }

        return rtrim($link->getBaseLink(), '/') . str_replace(_PS_ROOT_DIR_, '', $filePathname);
    }

    public function find(int $blockId, string $name, ?int $langId = null): ?string
    {
        $filename = $this->getFilename($blockId, $name, $langId);

        $files = glob("{$this->blockImageDir}$filename.*");

        if (!$files) {
            return null;
        }

        return $files[0];
    }

    private function getFilename(int $blockId, ?string $name = null, ?int $langId = null): string
    {
        $filename = $name !== null ? "$blockId/{$name}" : "$blockId/*";

        if ($langId !== null) {
            $isoCode = \Language::getIsoById($langId);

            $filename .= "_$isoCode";
        }

        return $filename;
    }
}
