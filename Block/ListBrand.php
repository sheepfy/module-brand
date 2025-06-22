<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Block;

use Blacksheep\Brand\Api\Data\BrandInterface;
use Blacksheep\Brand\Model\Brand;
use Blacksheep\Brand\Model\BrandUrlPathGenerator;
use Blacksheep\Brand\Model\Config\Source\Status;
use Blacksheep\Brand\Model\ResourceModel\Brand\Collection;
use Blacksheep\Brand\Model\ResourceModel\Brand\CollectionFactory;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class ListBrand extends Template implements IdentityInterface
{
    private ?Collection $collection = null;

    public function __construct(
        Context $context,
        private CollectionFactory $collectionFactory,
        private BrandUrlPathGenerator $brandUrlPathGenerator
    ) {
        parent::__construct($context);
    }

    public function getBrandsAToZ(): array
    {
        $brandsAz = [];

        $collection = $this->getBrandCollection();
        /** @var \Blacksheep\Brand\Model\Brand $brand */
        foreach ($collection as $brand) {
            $firstChar = mb_substr($brand->getName(), 0, 1, 'utf-8');
            $brandsAz[ctype_digit($firstChar) ? '0-9' : mb_strtoupper($firstChar)][] = $brand;
        }

        $moveLast = ['Å', 'Ä', 'Ö', '0-9'];
        foreach ($moveLast as $key) {
            if (!isset($brandsAz[$key])) {
                continue;
            }

            $toMove = $brandsAz[$key];
            unset($brandsAz[$key]);
            $brandsAz[$key] = $toMove;
        }

        return $brandsAz;
    }

    public function getBrandCollection(): Collection
    {
        if ($this->collection) {
            return $this->collection;
        }

        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToSelect([
            BrandInterface::ENTITY_ID,
            BrandInterface::NAME,
            BrandInterface::LOGO,
            BrandInterface::URL_KEY,
        ]);

        $collection->addFieldToFilter(BrandInterface::STATUS, ['eq' => Status::STATUS_ACTIVE]);
        $collection->addOrder(BrandInterface::NAME, Collection::SORT_ORDER_ASC);
        $collection->setLoadProductCount(true);
        $collection->addHasProductsFilter();

        $this->_eventManager->dispatch('catalog_brand_list_collection', [
            'collection' => $collection
        ]);

        return $this->collection = $collection;
    }

    public function getBrandUrl(BrandInterface $brand): string
    {
        return $this->getUrl() . $this->brandUrlPathGenerator->getUrlPathWithSuffix($brand);
    }

    public function getIdentities()
    {
        $tags = [];
        /** @var BrandInterface $brand */
        foreach ($this->getBrandCollection() as $brand) {
            $tags[] = Brand::CACHE_TAG . '_' . $brand->getId();
        }
        $tags[] = Brand::CACHE_TAG;

        return $tags;
    }
}
