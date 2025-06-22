<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\Indexer\Brand;

use Blacksheep\Brand\Model\Brand;
use Blacksheep\Brand\Model\Indexer\Brand\Product\TableMaintainer;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Indexer\CacheContext;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Product implements IndexerActionInterface, MviewActionInterface
{
    public const INDEXER_ID = 'catalog_brand_product';

    public const MAIN_INDEX_TABLE = 'catalog_brand_product_index';

    private const BATCH_SIZE = 100;

    private array $attributes = [];

    private array $brandIdsForCache = [];

    public function __construct(
        private CacheContext $cacheContext,
        private CollectionFactory $collectionFactory,
        private TableMaintainer $tableMaintainer,
        private StoreManagerInterface $storeManager,
        private ResourceConnection $resourceConnection,
        private MetadataPool $metadataPool,
        private EavConfig $eavConfig,
        private LoggerInterface $logger
    ) {}

    public function executeFull()
    {
        $this->execute([]);
    }

    public function executeList(array $ids)
    {
        $this->execute($ids);
    }

    public function executeRow($id)
    {
        $this->execute([$id]);
    }

    public function execute($ids)
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        if (!$this->getAttribute('visibility') || !$this->getAttribute('brand')) {
            $this->logger->error('Required attributes are missing: visibility, brand');

            return;
        }

        $isFullReindex = !$ids;
        $tablesToSwitch = [];
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int) $store->getId();

            $this->tableMaintainer->createTablesForStore($storeId);
            $this->tableMaintainer->clearReplicaTablesForStore($storeId);
            $this->tableMaintainer->createMainTmpTable($storeId);

            $this->process($ids, $isFullReindex, $storeId);

            $tablesToSwitch[] = $this->tableMaintainer->getMainTable($storeId);
        }

        if ($isFullReindex) {
            $this->tableMaintainer->switchTables($tablesToSwitch);
        }

        $this->cacheContext->registerEntities(Brand::CACHE_TAG, $this->brandIdsForCache);
    }

    private function process(array $ids, bool $isFullReindex, int $storeId): void
    {
        if (!$ids) {
            $ids = $this->getAllIds();
        }
        $brandAttr = $this->getAttribute('brand');
        $visibilityAttr = $this->getAttribute('visibility');
        $link = $this->getLinkField();
        $connection = $this->resourceConnection->getConnection();

        $connection->delete($this->tableMaintainer->getMainTmpTable($storeId));
        foreach (array_chunk($ids, self::BATCH_SIZE) as $chunk) {
            $select = $connection->select();
            $select->from(
                ['p' => $connection->getTableName('catalog_product_entity')],
                [
                    new \Zend_Db_Expr('IF(b.value, b.value, b_d.value) as brand_id'),
                    'p.entity_id as product_id',
                    new \Zend_Db_Expr("{$storeId} as store_id"),
                    new \Zend_Db_Expr('IF(v.value, v.value, v_d.value) as visibility'),
                ]
            );

            foreach (['b' => $brandAttr, 'v' => $visibilityAttr] as $k => $attr) {
                $select->joinLeft(
                    [$k => $attr->getBackendTable()],
                    "p.{$link} = {$k}.{$link} AND {$k}.attribute_id = {$attr->getId()} AND {$k}.store_id = {$storeId}",
                    ''
                );
                $select->joinLeft(
                    ["{$k}_d" => $attr->getBackendTable()],
                    "p.{$link} = {$k}_d.{$link} AND {$k}_d.attribute_id = {$attr->getId()} AND {$k}_d.store_id = 0",
                    ''
                );
            }

            $select->where(new \Zend_Db_Expr('IF(b.value, b.store_id, b_d.store_id) is not null'));
            $select->where('p.entity_id IN (?)', $chunk);

            $rows = $connection->fetchAll($select) ?: [];
            foreach ($rows as $row) {
                $this->brandIdsForCache[$row['brand_id']] = $row['brand_id'];
            }

            $connection->insertOnDuplicate(
                $this->tableMaintainer->getMainTmpTable($storeId),
                $rows,
                ['brand_id', 'product_id', 'store_id', 'visibility']
            );

            $this->tableMaintainer->publishReplicaData($storeId);

            if (!$isFullReindex) {
                $this->tableMaintainer->publishMainData($ids, $storeId);
            }
        }
    }

    private function getAttribute(string $attrCode): AttributeInterface
    {
        if ($attr = $this->attributes[$attrCode] ?? null) {
            return $attr;
        }

        return $this->attributes[$attrCode] = $this->eavConfig->getAttribute(
            CatalogProduct::ENTITY,
            $attrCode
        );
    }

    private function getLinkField(): string
    {
        return $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
    }

    private function getAllIds(): array
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToFilter('brand', ['neq' => null]);

        return $collection->getAllIds();
    }
}
