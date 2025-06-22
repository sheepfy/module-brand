<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Block\Product;

use Blacksheep\Brand\Model\Brand;
use Magento\Catalog\Block\Product\ProductList\Toolbar;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

class ListProduct extends \Magento\Catalog\Block\Product\ListProduct
{
    protected function _getProductCollection()
    {
        if ($this->_productCollection === null) {
            $this->_productCollection = $this->initializeProductCollection();
        }

        return $this->_productCollection;
    }

    public function getMode()
    {
        if ($this->getChildBlock('toolbar')) {
            return $this->getChildBlock('toolbar')->getCurrentMode();
        }

        return $this->getDefaultListingMode();
    }

    private function getDefaultListingMode()
    {
        // default Toolbar when the toolbar layout is not used
        $defaultToolbar = $this->getToolbarBlock();
        $availableModes = $defaultToolbar->getModes();

        // layout config mode
        $mode = $this->getData('mode');

        if (!$mode || !isset($availableModes[$mode])) {
            // default config mode
            $mode = $defaultToolbar->getCurrentMode();
        }

        return $mode;
    }

    protected function _beforeToHtml()
    {
        $collection = $this->_getProductCollection();

        $this->addToolbarBlock($collection);

        if (!$collection->isLoaded()) {
            $collection->load();
        }

        return $this;
    }

    private function addToolbarBlock(Collection $collection)
    {
        $toolbarLayout = $this->getToolbarFromLayout();

        if ($toolbarLayout) {
            $this->configureToolbar($toolbarLayout, $collection);
        }
    }

    public function getToolbarBlock()
    {
        return $this->getToolbarFromLayout() ?: $this->getLayout()->createBlock(
            $this->_defaultToolbarBlock,
            uniqid(microtime())
        );
    }

    private function getToolbarFromLayout()
    {
        $blockName = $this->getToolbarBlockName();

        return $blockName ? $this->getLayout()->getBlock($blockName) : false;
    }

    public function getIdentities(): array
    {
        $identities = [];

        $brand = $this->getLayer()->getCurrentBrand();
        if ($brand) {
            $identities[] = [Brand::CACHE_PRODUCT_BRAND_TAG . '_' . $brand->getEntityId()];
        }

        foreach ($this->_getProductCollection() as $item) {
            $identities[] = $item->getIdentities();
        }

        return array_merge(...$identities);
    }

    private function initializeProductCollection()
    {
        /** @var \Blacksheep\Brand\Model\Layer\Brand $layer */
        $layer = $this->getLayer();
        $collection = $layer->getProductCollection();

        $this->_eventManager->dispatch('catalog_block_product_list_collection', [
            'collection' => $collection
        ]);

        return $collection;
    }

    private function configureToolbar(Toolbar $toolbar, Collection $collection): void
    {
        $orders = $this->getAvailableOrders();
        if ($orders) {
            $toolbar->setAvailableOrders($orders);
        }
        $sort = $this->getSortBy();
        if ($sort) {
            $toolbar->setDefaultOrder($sort);
        }
        $dir = $this->getDefaultDirection();
        if ($dir) {
            $toolbar->setDefaultDirection($dir);
        }
        $modes = $this->getModes();
        if ($modes) {
            $toolbar->setModes($modes);
        }
        // set collection to toolbar and apply sort
        $toolbar->setCollection($collection);
        $this->setChild('toolbar', $toolbar);
    }
}
