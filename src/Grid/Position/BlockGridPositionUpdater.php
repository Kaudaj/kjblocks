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

namespace Kaudaj\Module\Blocks\Grid\Position;

use Kaudaj\Module\Blocks\Grid\Position\UpdateHandler\BlockPositionUpdateHandler;
use PrestaShop\PrestaShop\Core\Grid\Position\GridPositionUpdaterInterface;
use PrestaShop\PrestaShop\Core\Grid\Position\PositionUpdateInterface;

final class BlockGridPositionUpdater implements GridPositionUpdaterInterface
{
    /**
     * @var BlockPositionUpdateHandler
     */
    private $updateHandler;

    public function __construct(BlockPositionUpdateHandler $updateHandler)
    {
        $this->updateHandler = $updateHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function update(PositionUpdateInterface $positionUpdate)
    {
        $newPositions = $this->getNewPositions($positionUpdate);
        $this->sortByPositionValue($newPositions);
        $this->updateHandler->updatePositions($positionUpdate->getPositionDefinition(), $newPositions, $positionUpdate->getParentId());
    }

    /**
     * @param PositionUpdateInterface $positionUpdate
     *
     * @return array
     */
    private function getNewPositions(PositionUpdateInterface $positionUpdate)
    {
        $positions = $this->updateHandler->getCurrentPositions($positionUpdate->getPositionDefinition(), $positionUpdate->getParentId());

        /** @var PositionModificationInterface $rowModification */
        foreach ($positionUpdate->getPositionModificationCollection() as $rowModification) {
            $positions[$rowModification->getId()] = $rowModification->getNewPosition();
        }

        return $positions;
    }

    /**
     * @param array $positions
     */
    private function sortByPositionValue(&$positions)
    {
        asort($positions);
    }
}
