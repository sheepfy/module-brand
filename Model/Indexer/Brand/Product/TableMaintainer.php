<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\Indexer\Brand\Product;

use Blacksheep\Brand\Model\Indexer\Brand\Product as Indexer;
use Blacksheep\Brand\Model\ResourceModel\Indexer\ActiveTableSwitcher;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver as TableResolver;
use Magento\Framework\Search\Request\Dimension;
use Magento\Store\Model\Store;

class TableMaintainer
{
    private string $tmpTableSuffix = '_tmp';

    private string $additionalTableSuffix = '_replica';

    private array $mainTmpTable = [];

    public function __construct(
        private ResourceConnection $resourceConnection,
        private TableResolver $tableResolver,
        private ActiveTableSwitcher $activeTableSwitcher
    ) {}

    private function getTable(string $table): string
    {
        return $this->resourceConnection->getTableName($table);
    }

    private function createTable(string $mainTableName, string $newTableName): void
    {
        $connection = $this->resourceConnection->getConnection();
        if ($connection->isTableExists($newTableName)) {
            return;
        }

        $connection->createTable($connection->createTableByDdl($mainTableName, $newTableName));
    }

    private function dropTable(string $tableName): void
    {
        $connection = $this->resourceConnection->getConnection();
        if ($connection->isTableExists($tableName)) {
            $connection->dropTable($tableName);
        }
    }

    public function getMainTable(int $storeId): string
    {
        return $this->tableResolver->resolve(Indexer::MAIN_INDEX_TABLE, [
            new Dimension(Store::ENTITY, $storeId)
        ]);
    }

    public function createTablesForStore(int $storeId): void
    {
        $mainTableName = $this->getMainTable($storeId);
        $this->createTable(
            $this->getTable(Indexer::MAIN_INDEX_TABLE . $this->additionalTableSuffix),
            $mainTableName
        );

        $mainReplicaTableName = $this->getMainTable($storeId) . $this->additionalTableSuffix;

        $this->createTable(
            $this->getTable(Indexer::MAIN_INDEX_TABLE . $this->additionalTableSuffix),
            $mainReplicaTableName
        );
    }

    public function clearReplicaTablesForStore(int $storeId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->truncateTable($this->getMainReplicaTable($storeId));
    }

    public function switchTables(array $tablesToSwitch = []): void
    {
        if (!$tablesToSwitch) {
            return;
        }

        $this->activeTableSwitcher->switchTable(
            $this->resourceConnection->getConnection(),
            $tablesToSwitch
        );
    }

    public function dropTablesForStore(int $storeId): void
    {
        $mainTableName = $this->getMainTable($storeId);
        $this->dropTable($mainTableName);

        $mainReplicaTableName = $this->getMainTable($storeId) . $this->additionalTableSuffix;
        $this->dropTable($mainReplicaTableName);
    }

    public function getMainReplicaTable(int $storeId): string
    {
        return $this->getMainTable($storeId) . $this->additionalTableSuffix;
    }

    public function createMainTmpTable(int $storeId): void
    {
        if (!isset($this->mainTmpTable[$storeId])) {
            $originTableName = $this->getMainTable($storeId);
            $temporaryTableName = $this->getMainTable($storeId) . $this->tmpTableSuffix;
            $connection = $this->resourceConnection->getConnection();
            $connection->createTemporaryTableLike($temporaryTableName, $originTableName, true);
            $this->mainTmpTable[$storeId] = $temporaryTableName;
        }
    }

    public function getMainTmpTable(int $storeId): string
    {
        if (!isset($this->mainTmpTable[$storeId])) {
            throw new NoSuchEntityException(__('Temporary table does not exist'));
        }

        return $this->mainTmpTable[$storeId];
    }

    public function publishReplicaData(int $storeId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from($this->getMainTmpTable($storeId));
        $columns = array_keys($connection->describeTable($this->getMainReplicaTable($storeId)));
        $tableName = $this->getMainReplicaTable($storeId);

        $connection->query(
            $connection->insertFromSelect($select, $tableName, $columns, AdapterInterface::INSERT_ON_DUPLICATE)
        );
    }

    public function publishMainData(array $ids, int $storeId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->delete($this->getMainTable($storeId), ['product_id IN (?)' => $ids]);
        $select = $connection->select()->from($this->getMainReplicaTable($storeId));
        $connection->query(
            $connection->insertFromSelect($select,
                $this->getMainTable($storeId),
                [],
                AdapterInterface::INSERT_ON_DUPLICATE
            )
        );
    }
}
