<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Observer;

use Blacksheep\Brand\Api\BrandRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddBrandObjectToProduct implements ObserverInterface
{
    public function __construct(
        private BrandRepositoryInterface $brandRepository,
        private SearchCriteriaBuilderFactory $searchCriteriaBuilder,
        private ProductResource $productResource
    ) {}

    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $observer->getData('collection');
        if (!$collection) {
            return;
        }

        if ($collection->getFlag('skip_brand_load')) {
            return;
        }


        if (!$this->productResource->getAttribute('brand')) {
            return;
        }

        $productsByBrand = [];
        foreach ($collection as $product) {
            $brandId = (int) $product->getBrand();
            if (!$brandId) {
                continue;
            }

            if (!isset($productsByBrand[$brandId])) {
                $productsByBrand[$brandId] = [];
            }

            $productsByBrand[$brandId][] = $product;
        }


        $brandIds = array_keys($productsByBrand);
        if (!$brandIds) {
            return;
        }

        /** @var \Magento\Framework\Api\SearchCriteriaBuilder $builder */
        $builder = $this->searchCriteriaBuilder->create();
        $builder->addFilter('entity_id', $brandIds, 'in');

        $brandSearchResults = $this->brandRepository->getList($builder->create());
        if (!$brandSearchResults->getTotalCount()) {
            return;
        }

        $brands = $brandSearchResults->getItems();
        /** @var \Magento\Catalog\Model\Product[] $products */
        foreach ($productsByBrand as $brandId => $products) {
            $brand = $brands[$brandId] ?? null;
            if (!$brand) {
                continue;
            }

            foreach ($products as $product) {
                /** @var \Magento\Catalog\Api\Data\ProductExtension $extensionAttributes */
                $extensionAttributes = $product->getExtensionAttributes();
                $extensionAttributes->setBrand($brand);
            }
        }
    }
}
