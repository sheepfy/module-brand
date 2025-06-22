<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const XML_PATH_BRAND_CHANGEFREQ = 'sitemap/brand/changefreq';
    public const XML_PATH_BRAND_PRIORITY = 'sitemap/brand/priority';
    public const XML_PATH_BRAND_IMAGE_INCLUDE = 'sitemap/brand/image_include';
    public const XML_PATH_USE_BRAND_CANONICAL_TAG = 'catalog/seo/brand_canonical_tag';

    public function __construct(
        private ScopeConfigInterface $scopeConfig
    ) {}

    public function getBrandsChangefreq(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_BRAND_CHANGEFREQ,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getBrandPriority(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_BRAND_PRIORITY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getBrandImageIncludePolicy(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_BRAND_IMAGE_INCLUDE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function canUseCanonicalTag(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_USE_BRAND_CANONICAL_TAG,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
