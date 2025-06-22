<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\ResourceModel\Brand;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    private bool $loadWithProductCount = false;

    private bool $isProductCountLoaded = false;

    protected function _construct()
    {
        $this->_init(\Blacksheep\Brand\Model\Brand::class, \Blacksheep\Brand\Model\ResourceModel\Brand::class);
    }

    public function toOptionArray()
    {
        return $this->_toOptionArray('entity_id', 'url_key');
    }

    public function toOptionHash()
    {
        return $this->_toOptionHash('entity_id', 'url_key');
    }

    public function setLoadProductCount(bool $flag): self
    {
        $this->loadWithProductCount = $flag;

        return $this;
    }

    public function load($printQuery = false, $logQuery = false)
    {
        parent::load($printQuery, $logQuery);

        if ($this->loadWithProductCount) {
            $this->loadProductCount($this->_items);
        }

        return $this;
    }

    public function loadProductCount(array $items): void
    {
        if ($this->isProductCountLoaded) {
            return;
        }

        $brandIds = array_keys($items);
        $select = $this->getConnection()->select();
        $select->from(
            ['main_table' => $this->getTable('catalog_brand_product_index')],
            ['brand_id', new \Zend_Db_Expr('COUNT(DISTINCT(main_table.product_id))')]
        );
        $select->where($this->getConnection()->quoteInto('main_table.brand_id IN(?)', $brandIds));
        $select->group('main_table.brand_id');

        $counts = $this->getConnection()->fetchPairs($select);
        foreach ($items as $item) {
            $item->setProductCount((int)($counts[$item->getEntityId()] ?? 0));
        }

        $this->isProductCountLoaded = true;
    }

    public function addHasProductsFilter(): void
    {
        $subSelect = $this->getConnection()->select();
        $subSelect->from(
            ['ccbi' => $this->getTable('catalog_brand_product_index')],
            ['ccbi.brand_id']
        );
        $subSelect->where('ccbi.visibility IN (?)', [
            Visibility::VISIBILITY_BOTH,
            Visibility::VISIBILITY_IN_CATALOG,
        ]);

        $this->getSelect()->where('main_table.entity_id IN (?)', $subSelect);
    }
}
