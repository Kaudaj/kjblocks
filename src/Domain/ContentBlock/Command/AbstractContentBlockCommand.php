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

namespace Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Command;

use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\Exception\ContentBlockConstraintException;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\ValueObject\Content;
use Kaudaj\Module\ContentBlocks\Domain\ContentBlock\ValueObject\Name;
use PrestaShop\PrestaShop\Adapter\Configuration;

/**
 * Class AbstractContentBlockCommand is responsible for handling configurator operations.
 */
class AbstractContentBlockCommand
{
    /**
     * @var int
     */
    protected $defaultLangId;

    public function __construct()
    {
        $this->defaultLangId = (new Configuration())->getInt('PS_LANG_DEFAULT');
    }

    /**
     * @param array<int, string> $localizedNames
     *
     * @return array<int, Name>
     */
    protected function mapLocalizedNames(array $localizedNames): array
    {
        if (!key_exists($this->defaultLangId, $localizedNames)) {
            throw new ContentBlockConstraintException('Block name is required at least in your default language.', ContentBlockConstraintException::EMPTY_NAME);
        }

        $localizedNamesMap = [];

        foreach ($localizedNames as $langId => $name) {
            $localizedNamesMap[$langId] = new Name($name);
        }

        return $localizedNamesMap;
    }

    /**
     * @param array<int, string> $localizedContents
     *
     * @return array<int, Content>
     */
    protected function mapLocalizedContents(array $localizedContents): array
    {
        if (!key_exists($this->defaultLangId, $localizedContents)) {
            throw new ContentBlockConstraintException('Block content is required at least in your default language.', ContentBlockConstraintException::EMPTY_CONTENT);
        }

        $localizedContentsMap = [];

        foreach ($localizedContents as $langId => $name) {
            $localizedContentsMap[$langId] = new Content($name);
        }

        return $localizedContentsMap;
    }
}
