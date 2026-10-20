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

/**
 * What a save of the block form writes on the row of the scope being edited.
 *
 * A save replaces the whole options of that row, so the options the form was
 * built from are not enough: a block type may have options its form has no
 * field for (a block created by a migration, a type whose form is the plain
 * container one), and the form cannot show a value that lives on a wider scope
 * either. This merges the submitted options into what the row already holds:
 *
 * - an option the form has no field for keeps its stored value;
 * - an option the form has a field for takes the submitted value (checked, in
 *   a shop or group context), or is dropped when the field was emptied;
 * - an option left unchecked in a shop or group context means "use the value of
 *   the wider scope": it is dropped from the row when a wider scope has one, and
 *   keeps its stored value when none has (there is nothing to fall back on, and
 *   dropping it would only lose it).
 */
class BlockOptionsMerger
{
    /**
     * @param array<string, mixed> $submitted options built from the form: the checked, non-empty fields
     * @param array<string, mixed> $stored options now stored on the row of the scope being edited
     * @param array<string, mixed> $inherited options the scope falls back on, from the wider scopes' rows
     * @param string[] $formOptionNames the options the form has a field for
     * @param string[] $uncheckedOptionNames the form options left unchecked ("use the wider scope's value")
     *
     * @return array<string, mixed>
     */
    public function merge(array $submitted, array $stored, array $inherited, array $formOptionNames, array $uncheckedOptionNames): array
    {
        // What the form cannot show stays as it is.
        $options = array_diff_key($stored, array_flip($formOptionNames));

        foreach ($uncheckedOptionNames as $name) {
            if (key_exists($name, $stored) && !key_exists($name, $inherited)) {
                $options[$name] = $stored[$name];
            }
        }

        return array_merge($options, $submitted);
    }
}
