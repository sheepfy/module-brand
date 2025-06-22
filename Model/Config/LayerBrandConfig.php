<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class LayerBrandConfig
{
    private const XML_PATH_CATALOG_LAYERED_NAVIGATION_DISPLAY_BRAND = 'catalog/layered_navigation/display_brand';

    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private StoreManagerInterface $storeManager
    ) {}

    public function isBrandFilterVisibleInLayerNavigation(
        $scopeType = ScopeInterface::SCOPE_STORES,
        $scopeCode = null
    ): bool {
        if (!$scopeCode) {
            $scopeCode = $this->getStoreId();
        }

        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CATALOG_LAYERED_NAVIGATION_DISPLAY_BRAND,
            $scopeType,
            $scopeCode
        );
    }

    private function getStoreId(): int
    {
        return (int) $this->storeManager->getStore()->getId();
    }
}
