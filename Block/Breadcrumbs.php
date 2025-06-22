<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Block;

use Blacksheep\Brand\Api\BrandRepositoryInterface;
use Blacksheep\Brand\Api\Data\BrandInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class Breadcrumbs extends Template
{
    public function __construct(
        Context $context,
        private BrandRepositoryInterface $brandRepository,
        private UrlFinderInterface $urlFinder,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        if (!$breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs')) {
            return parent::_prepareLayout();
        }

        $store = $this->_storeManager->getStore();
        $breadcrumbsBlock->addCrumb('home', [
            'label' => __('Home')->render(),
            'title' => __('Go to Home Page')->render(),
            'link' => $store->getBaseUrl()
        ]);

        $breadCrumbData = [
            'label' => __('Brands')->render(),
            'title' => __('Brands')->render(),
        ];

        $brand = $this->getBrand();
        if ($brand) {
            $breadCrumbData['link'] = $this->getBrandListingUrl((int) $store->getId());
        }

        $breadcrumbsBlock->addCrumb('brands', $breadCrumbData);

        if ($brand) {
            $breadcrumbsBlock->addCrumb('brand', ['label' => $brand->getName()]);
            $this->pageConfig->getTitle()->set($brand->getName());
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

    private function getBrandListingUrl(int $storeId): string
    {
        $targetPath = 'catalog/brand/list';

        try {
            $rewrite = $this->urlFinder->findOneByData([
                UrlRewrite::TARGET_PATH => ltrim($targetPath, '/'),
                UrlRewrite::STORE_ID => $storeId,
            ]);
        } catch (\Exception $e) {
            $rewrite = null;
        }

        return $rewrite ?
            $this->_urlBuilder->getDirectUrl($rewrite->getRequestPath()) : $this->_urlBuilder->getUrl($targetPath);
    }
}
