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
use PrestaShop\PrestaShop\Core\Grid\Position\Exception\PositionUpdateException;
use PrestaShop\PrestaShop\Core\Grid\Position\GridPositionUpdaterInterface;
use PrestaShop\PrestaShop\Core\Grid\Position\PositionModificationInterface;
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
        $this->updateHandler->updatePositions($positionUpdate->getPositionDefinition(), $newPositions, $positionUpdate->getParentId());
    }

    /**
     * Mirrors PrestaShop core GridPositionUpdater::getNewPositions().
     *
     * The current positions are first re-indexed to be consecutive (based on the defined
     * first position) so raw database values - which may be 1-based or contain gaps - never
     * leak into the computation. The drag modifications are then applied, ties are broken by
     * the intended move direction, and the result is re-indexed with no gap.
     *
     * @return array<int, int> [rowId => newPosition]
     */
    private function getNewPositions(PositionUpdateInterface $positionUpdate)
    {
        // Positions are returned ascending
        $currentPositions = $this->updateHandler->getCurrentPositions($positionUpdate->getPositionDefinition(), $positionUpdate->getParentId());

        // Build positions data [rowId, currentPosition, newPosition]
        $rowPositions = [];

        // First make sure current positions are correctly indexed (based on defined first position) and consecutive
        $firstPosition = $positionUpdate->getPositionDefinition()->getFirstPosition();
        $positionIndex = $firstPosition;
        $allRowIds = array_keys($currentPositions);
        foreach ($allRowIds as $rowId) {
            $rowPositions[$rowId] = [
                'rowId' => $rowId,
                'currentPosition' => $positionIndex,
                'newPosition' => null,
            ];
            ++$positionIndex;
        }

        // Then define new positions (keep track of new position values, doublons are not accepted)
        $newPositionIndexes = [];
        /** @var PositionModificationInterface $rowModification */
        foreach ($positionUpdate->getPositionModificationCollection() as $rowModification) {
            if (!in_array($rowModification->getId(), $allRowIds)) {
                throw new PositionUpdateException('No row identified by %rowId% in the list', 'Admin.Catalog.Notification', ['%rowId%' => $rowModification->getId()]);
            }
            if (in_array($rowModification->getNewPosition(), $newPositionIndexes)) {
                throw new PositionUpdateException('You cannot set the same new position for two elements', 'Admin.Catalog.Notification');
            }

            $newPosition = $rowModification->getNewPosition();
            if ($newPosition < $firstPosition) {
                $newPosition = $firstPosition;
            } elseif ($newPosition > count($allRowIds) - 1 + $firstPosition) {
                $newPosition = count($allRowIds) - 1 + $firstPosition;
            }

            $rowPositions[$rowModification->getId()]['newPosition'] = $newPosition;
            $newPositionIndexes[] = $rowModification->getNewPosition();
        }

        usort($rowPositions, static function (array $rowPositionA, array $rowPositionB): int {
            // Using ?? is important here (?: would ignore a value 0 and wrongly use the fallback,
            // ?? uses the fallback only if newPosition is not set)
            $positionA = $rowPositionA['newPosition'] ?? $rowPositionA['currentPosition'];
            $positionB = $rowPositionB['newPosition'] ?? $rowPositionB['currentPosition'];

            // In case both positions are equal we prioritize by valuing the expected modification:
            // a modified row aiming higher/lower is nudged in that direction so it wins the tie.
            if ($positionA === $positionB) {
                if (null !== $rowPositionA['newPosition']) {
                    $positionA = $positionA + $rowPositionA['newPosition'] - $rowPositionA['currentPosition'];
                }
                if (null !== $rowPositionB['newPosition']) {
                    $positionB = $positionB + $rowPositionB['newPosition'] - $rowPositionB['currentPosition'];
                }
            }

            return $positionA < $positionB ? -1 : 1;
        });

        // Now that rows are ordered we recompute the new positions array, making sure it is
        // indexed based on first position, and not missing a position
        $newPositions = [];
        $positionIndex = $firstPosition;
        foreach ($rowPositions as $rowPosition) {
            $newPositions[(int) $rowPosition['rowId']] = $positionIndex;
            ++$positionIndex;
        }

        return $newPositions;
    }
}
