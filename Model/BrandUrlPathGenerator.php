<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model;

use Blacksheep\Brand\Api\Data\BrandInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filter\FilterManager;
use Magento\Store\Model\ScopeInterface;

class BrandUrlPathGenerator
{
    public const XML_PATH_BRAND_URL_PREFIX = 'catalog/seo/brand_url_prefix';

    public const XML_PATH_BRAND_URL_SUFFIX = 'catalog/seo/brand_url_suffix';

    private array $brandUrlSuffix = [];

    private array $urlPathPrefix = [];

    public function __construct(
        private FilterManager $filterManager,
        private ScopeConfigInterface $scopeConfig
    ) {}

    public function getUrlPath(BrandInterface $brand): string
    {
        $urlPathPrefix = $this->getBrandUrlPrefix();

        return $urlPathPrefix ? $urlPathPrefix . '/' . $brand->getUrlKey() : $brand->getUrlKey();
    }

    public function getUrlPathWithSuffix(BrandInterface $brand, ?int $storeId = null): string
    {
        return $this->getUrlPath($brand) . $this->getBrandUrlSuffix($storeId);
    }

    public function getCanonicalUrlPath(BrandInterface $brand): string
    {
        return 'catalog/brand/view/brand_id/' . $brand->getId();
    }

    public function generateUrlKey(BrandInterface $brand): string
    {
        return $this->filterManager->translitUrl($brand->getUrlKey() ?: $brand->getName());
    }

    private function getBrandUrlSuffix(?int $storeId = null): string
    {
        $storeId = (int) $storeId;
        if (!isset($this->brandUrlSuffix[$storeId])) {
            $this->brandUrlSuffix[$storeId] = (string) $this->scopeConfig->getValue(
                self::XML_PATH_BRAND_URL_SUFFIX,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        return $this->brandUrlSuffix[$storeId];
    }

    private function getBrandUrlPrefix(?int $storeId = null): string
    {
        $storeId = (int) $storeId;
        if (!isset($this->urlPathPrefix[$storeId])) {
            $this->urlPathPrefix[$storeId] = (string) $this->scopeConfig->getValue(
                self::XML_PATH_BRAND_URL_PREFIX,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        return $this->urlPathPrefix[$storeId];
    }
}
