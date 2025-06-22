<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Block\Product\View;

use Blacksheep\Brand\Api\BrandRepositoryInterface;
use Blacksheep\Brand\Api\Data\BrandInterface;
use Blacksheep\Brand\Block\Brand\Image;
use Blacksheep\Brand\Block\Brand\ImageFactory;
use Blacksheep\Brand\Helper\Image as BrandImageHelper;
use Blacksheep\Brand\Model\BrandUrlPathGenerator;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Brand extends Template implements IdentityInterface
{
    public function __construct(
        Context $context,
        private ProductRepositoryInterface $productRepository,
        private BrandRepositoryInterface $brandRepository,
        private BrandUrlPathGenerator $brandUrlPathGenerator,
        private ImageFactory $imageFactory,
        private BrandImageHelper $brandImageHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _toHtml()
    {
        $brand = $this->getBrand();
        if (!$brand || !$brand->isActive()) {
            return '';
        }

        return parent::_toHtml();
    }

    public function getProduct(): ProductInterface
    {
        $productId = $this->getProductId();
        $storeId = (int)$this->_storeManager->getStore()->getId();

        return $this->productRepository->getById($productId, false, $storeId);
    }

    private function getProductId(): int
    {
        // @phpstan-ignore-next-line
        if ($this->getRequest()->getFullActionName() === 'catalog_product_view') {
            return (int) $this->getRequest()->getParam('id');
        }

        // @phpstan-ignore-next-line
        if ($this->getRequest()->getActionName() === 'configure') {
            return (int) $this->getRequest()->getParam('product_id');
        }

        return 0;
    }

    public function getBrandImage(BrandInterface $brand, string $imageId, array $attributes = []): Image
    {
        $this->brandImageHelper->init($brand, $imageId, $attributes)->getUrl();

        return $this->imageFactory->create($brand, $imageId, $attributes);
    }

    public function getBrand(): ?BrandInterface
    {
        try {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->getProduct();
            $brandId = (int) $product->getBrand();

            return $brandId ? $this->brandRepository->getById($brandId) : null;
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    public function getBrandUrl(BrandInterface $brand): string
    {
        return $this->getUrl() . $this->brandUrlPathGenerator->getUrlPathWithSuffix($brand);
    }

    public function getIdentities(): array
    {
        return $this->getBrand()->getIdentities();
    }
}
