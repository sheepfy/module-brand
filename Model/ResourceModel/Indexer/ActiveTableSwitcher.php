<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\ResourceModel\Indexer;

use Magento\Framework\DB\Adapter\AdapterInterface;

class ActiveTableSwitcher
{
    private string $additionalTableSuffix = '_replica';

    private string $outdatedTableSuffix = '_outdated';

    public function switchTable(AdapterInterface $connection, array $tableNames): void
    {
        $toRename = [];
        foreach ($tableNames as $tableName) {
            $outdatedTableName = $tableName . $this->outdatedTableSuffix;
            $replicaTableName = $tableName . $this->additionalTableSuffix;

            $tableComment = $connection->showTableStatus($tableName)['Comment'] ?? '';
            $replicaComment = $connection->showTableStatus($replicaTableName)['Comment'] ?? '';

            $toRename[] = [
                [
                    'oldName' => $tableName,
                    'newName' => $outdatedTableName,
                ],
                [
                    'oldName' => $replicaTableName,
                    'newName' => $tableName,
                ],
                [
                    'oldName' => $outdatedTableName,
                    'newName' => $replicaTableName,
                ],
            ];

            if ($toRename && $replicaComment !== '' && $tableComment !== $replicaComment) {
                $connection->changeTableComment($tableName, $replicaComment);
                $connection->changeTableComment($replicaTableName, $tableComment);
            }
        }

        $toRename = array_merge([], ...$toRename);
        if ($toRename) {
            $connection->renameTablesBatch($toRename);
        }
    }

    public function getAdditionalTableName(string $tableName): string
    {
        return $tableName . $this->additionalTableSuffix;
    }
}
