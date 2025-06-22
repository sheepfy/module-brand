<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\Layer;

use Blacksheep\Brand\Api\BrandRepositoryInterface;
use Blacksheep\Brand\Api\Data\BrandInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\ContextInterface;
use Magento\Catalog\Model\Layer\StateFactory;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;

class Brand extends Layer
{
    public function __construct(
        ContextInterface $context,
        StateFactory $layerStateFactory,
        AttributeCollectionFactory $attributeCollectionFactory,
        Product $catalogProduct,
        StoreManagerInterface $storeManager,
        Registry $registry,
        CategoryRepositoryInterface $categoryRepository,
        private RequestInterface $request,
        private BrandRepositoryInterface $brandRepository,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $layerStateFactory,
            $attributeCollectionFactory,
            $catalogProduct,
            $storeManager,
            $registry,
            $categoryRepository,
            $data
        );
    }

    public function prepareProductCollection($collection)
    {
        $collection->addFieldToFilter('brand', $this->getCurrentBrand()->getId());

        return parent::prepareProductCollection($collection);
    }

    public function getCurrentBrand(): ?BrandInterface
    {
        try {
            $id = (int) $this->request->getParam('brand_id');

            return $id ? $this->brandRepository->getById($id) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setCurrentBrand($brand)
    {
        if (is_numeric($brand)) {
            try {
                $brand = $this->brandRepository->getById((int) $brand);
            } catch (NoSuchEntityException $e) {
                throw new LocalizedException(__('Provided brand does not exist.'), $e);
            }
        } elseif ($brand instanceof BrandInterface) {
            if (!$brand->getId()) {
                throw new LocalizedException(__('Provided brand no longer exists.'));
            }
        } else {
            throw new LocalizedException(__(
                'Brand parameter be an object of type "BrandInterface" or its brand id.'
            ));
        }

        if ($brand->getId() != $this->getCurrentBrand()->getId()) {
            $this->setData('current_brand', $brand);
        }

        return $this;
    }
}
