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

namespace Kaudaj\Module\Blocks\File;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class BlockFileManager
{
    /**
     * @var string
     */
    private $destinationDir;

    public function __construct(string $destinationDir)
    {
        $this->destinationDir = $destinationDir;
    }

    public function upload(int $blockId, UploadedFile $file, ?string $destinationFilename = null): File
    {
        $maxSize = \Tools::getMaxUploadSize();

        if ($file->getSize() > $maxSize) {
            throw new FileException('Max size exceeded.');
        }

        if (!$destinationFilename) {
            $destinationFilename = $this->generateUniqueFilename($blockId);
        }

        $destinationPathname = $this->getPathname($blockId, $destinationFilename);

        return $file->move(dirname($destinationPathname), basename($destinationPathname));
    }

    public function delete(?int $blockId = null, ?string $filename = null): void
    {
        $fullFilename = $this->getFilename($blockId, $filename);

        $finder = (new Finder())
            ->in($this->destinationDir)
        ;

        if ($blockId !== null) {
            $finder->path(dirname($fullFilename));
        }

        if ($filename !== null) {
            $finder->name(basename($fullFilename));
            $finder->files();
        } else {
            $finder->directories();
        }

        $filesystem = new Filesystem();
        foreach (iterator_to_array($finder->getIterator()) as $file) {
            $filesystem->remove($file->getRealPath());
        }
    }

    public function getFilename(?int $blockId = null, ?string $filename = null): string
    {
        if ($blockId === null) {
            return '*';
        }

        return "$blockId/" . ($filename !== null ? $filename : '*');
    }

    public function getPathname(?int $blockId = null, ?string $filename = null): string
    {
        return $this->destinationDir . $this->getFilename($blockId, $filename);
    }

    public function generateUniqueFilename(int $blockId): string
    {
        do {
            $uniqueFilename = sha1(uniqid());
        } while (file_exists($this->destinationDir . $this->getPathname($blockId, $uniqueFilename)));

        return $uniqueFilename;
    }

    public function find(int $blockId, string $filename): ?string
    {
        $filename = $this->getFilename($blockId, $filename);

        $files = glob("{$this->destinationDir}$filename.*");

        if (!$files) {
            return null;
        }

        return $files[0];
    }

    public function getUrl(int $blockId, string $filename): ?string
    {
        $filePathname = $this->find($blockId, $filename);
        $link = new \Link();

        if ($filePathname === null) {
            return null;
        }

        return rtrim($link->getBaseLink(), '/') . str_replace(_PS_ROOT_DIR_, '', $filePathname);
    }
}
