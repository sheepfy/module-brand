<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Block\Brand;

use Blacksheep\Brand\Api\BrandRepositoryInterface;
use Blacksheep\Brand\Api\Data\BrandInterface;
use Blacksheep\Brand\Helper\Image as BrandImageHelper;
use Blacksheep\Brand\Model\Brand;
use Blacksheep\Brand\Model\Config;
use Magento\Catalog\Helper\Data as CatalogDataHelper;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class View extends Template implements IdentityInterface
{
    public function __construct(
        Context $context,
        private Config $config,
        private BrandRepositoryInterface $brandRepository,
        private CatalogDataHelper $catalogData,
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

    protected function _prepareLayout()
    {
        if (!$brand = $this->getBrand()) {
            return $this;
        }

        $storeId = (int) $this->_storeManager->getStore()->getId();

        $title = $brand->getMetaTitle();
        if ($title) {
            $this->pageConfig->getTitle()->set($title);
        }

        $description = $brand->getMetaDescription();
        if ($description) {
            $this->pageConfig->setDescription($description);
        }

        $keywords = $brand->getMetaKeywords();
        if ($keywords) {
            $this->pageConfig->setKeywords($keywords);
        }

        if ($this->config->canUseCanonicalTag($storeId)) {
            $this->pageConfig->addRemotePageAsset(
                $brand->getUrl(),
                'canonical',
                ['attributes' => ['rel' => 'canonical']]
            );
        }

        $pageMainTitle = $this->getLayout()->getBlock('page.main.title');
        if ($pageMainTitle) {
            $pageMainTitle->setPageTitle($brand->getName());
        }

        $pageContentTitle = $this->getLayout()->getBlock('content.page.title');
        $pageTitle = $brand->getPageTitle() ?: $brand->getName();
        if ($pageContentTitle) {
            $pageContentTitle->setPageTitle($pageTitle);
        }

        return $this;
    }

    public function getBrand(): ?BrandInterface
    {
        try {
            $id = (int) $this->getRequest()->getParam('brand_id');

            return $id ? $this->brandRepository->getById($id) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getBrandImage(BrandInterface $brand, string $imageId, array $attributes = []): Image
    {
        $this->brandImageHelper->init($brand, $imageId, $attributes)->getUrl();

        return $this->imageFactory->create($brand, $imageId, $attributes);
    }

    public function getDescription(): ?string
    {
        $description = $this->getBrand()->getDescription();
        if (!$description) {
            return null;
        }

        return $this->catalogData->getPageTemplateProcessor()->filter($description);
    }

    public function getProductListHtml(): string
    {
        return $this->getChildHtml('product_list');
    }

    public function isProductMode(): bool
    {
        return true;
    }

    public function isMixedMode(): bool
    {
        return false;
    }

    public function isContentMode(): bool
    {
        return false;
    }

    public function getIdentities(): array
    {
        return [
            Brand::CACHE_PRODUCT_BRAND_TAG,
            Brand::CACHE_PRODUCT_BRAND_TAG . '_' . $this->getBrand()->getId(),
        ];
    }
}
