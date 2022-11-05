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

namespace Kaudaj\Module\Blocks\Grid\Position\UpdateHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Statement;
use PrestaShop\PrestaShop\Core\Grid\Position\Exception\PositionUpdateException;
use PrestaShop\PrestaShop\Core\Grid\Position\PositionDefinitionInterface;

final class BlockPositionUpdateHandler
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $dbPrefix;

    /**
     * @param Connection $connection
     * @param string $dbPrefix
     */
    public function __construct(
        Connection $connection,
        $dbPrefix
    ) {
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;
    }

    public function getCurrentPositions(PositionDefinitionInterface $positionDefinition, $parentId = null)
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->from($this->dbPrefix . $positionDefinition->getTable(), 't')
            ->select('t.' . $positionDefinition->getIdField() . ', t.' . $positionDefinition->getPositionField())
            ->addOrderBy('t.' . $positionDefinition->getPositionField(), 'ASC');

        if (null !== $parentId && null !== $positionDefinition->getParentIdField()) {
            $qb
                ->andWhere('t.' . $positionDefinition->getParentIdField() . ' = :parentId')
                ->setParameter('parentId', $parentId);
        }

        $positions = $qb->execute()->fetchAll();
        $currentPositions = [];
        foreach ($positions as $position) {
            $positionId = $position[$positionDefinition->getIdField()];
            $currentPositions[$positionId] = $position[$positionDefinition->getPositionField()];
        }

        return $currentPositions;
    }

    public function updatePositions(PositionDefinitionInterface $positionDefinition, array $newPositions, int $parentId)
    {
        try {
            $this->connection->beginTransaction();
            $positionIndex = $positionDefinition->getFirstPosition();
            foreach ($newPositions as $rowId => $newPosition) {
                $qb = $this->connection->createQueryBuilder();
                $qb
                    ->update($this->dbPrefix . $positionDefinition->getTable())
                    ->set($positionDefinition->getPositionField(), ':position')
                    ->andWhere($positionDefinition->getIdField() . ' = :rowId')
                    ->andWhere($positionDefinition->getParentIdField() . ' = :parentId')
                    ->setParameter('rowId', $rowId)
                    ->setParameter('position', $positionIndex)
                    ->setParameter('parentId', $parentId);

                $statement = $qb->execute();
                if ($statement instanceof Statement && $statement->errorCode()) {
                    throw new PositionUpdateException('Could not update #%i', 'Admin.Catalog.Notification', [$rowId]);
                }
                ++$positionIndex;
            }
            $this->connection->commit();
        } catch (ConnectionException $e) {
            $this->connection->rollBack();

            throw new PositionUpdateException('Could not update.', 'Admin.Catalog.Notification');
        }
    }
}
