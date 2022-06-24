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

namespace Kaudaj\Module\ContentBlocks\Form\ChoiceProvider;

/**
 * Class HookChoiceProvider.
 */
final class HookChoiceProvider extends AbstractDatabaseChoiceProvider
{
    /**
     * @return array<string, int>
     */
    public function getChoices(): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('h.id_hook, h.name')
            ->from($this->dbPrefix . 'hook', 'h')
            ->andWhere('h.name LIKE :displayHook')
            ->setParameter('displayHook', 'display%')
            ->orderBy('h.name')
        ;

        $hooks = $qb->execute()->fetchAll(); // @phpstan-ignore-line

        $choices = [];
        foreach ($hooks as $hook) {
            if (!is_array($hook)) {
                continue;
            }

            $choices[strval($hook['name'])] = intval($hook['id_hook']);
        }

        return $choices;
    }
}
