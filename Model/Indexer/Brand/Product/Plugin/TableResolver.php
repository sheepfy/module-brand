<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\Indexer\Brand\Product\Plugin;

use Blacksheep\Brand\Model\Indexer\Brand\Product as Indexer;
use Blacksheep\Brand\Model\Indexer\Brand\Product\TableMaintainer;
use Magento\Framework\App\ResourceConnection as Subject;
use Magento\Store\Model\StoreManagerInterface;

class TableResolver
{
    public function __construct(
        private StoreManagerInterface $storeManager,
        private TableMaintainer $tableMaintainer
    ) {}

    public function afterGetTableName(Subject $subject, string $result, $modelEntity): string
    {
        if (!is_array($modelEntity)
            && $modelEntity === Indexer::MAIN_INDEX_TABLE
            && $this->storeManager->getStore()->getId()
        ) {
            return $this->tableMaintainer->getMainTable((int) $this->storeManager->getStore()->getId());
        }

        return $result;
    }
}
