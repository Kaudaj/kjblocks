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

namespace Kaudaj\Module\Blocks\Domain\Block\Command;

use Kaudaj\Module\Blocks\Domain\Block\Exception\BlockConstraintException;
use Kaudaj\Module\Blocks\Domain\Block\ValueObject\Name;
use PrestaShop\PrestaShop\Adapter\Configuration;

/**
 * Class AbstractBlockCommand is responsible for handling configurator operations.
 */
class AbstractBlockCommand
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
            throw new BlockConstraintException('Block name is required at least in your default language.', BlockConstraintException::EMPTY_NAME);
        }

        $localizedNamesMap = [];

        foreach ($localizedNames as $langId => $name) {
            $localizedNamesMap[$langId] = new Name($name);
        }

        return $localizedNamesMap;
    }
}
