<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\Sitemap\ItemProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sitemap\Model\ItemProvider\ConfigReaderInterface;
use Magento\Store\Model\ScopeInterface;

class BrandConfigReader implements ConfigReaderInterface
{
    public const XML_PATH_PRIORITY = 'sitemap/brand/priority';

    public const XML_PATH_CHANGE_FREQUENCY = 'sitemap/brand/changefreq';

    public function __construct(
        private ScopeConfigInterface $scopeConfig
    ) {}

    public function getPriority($storeId)
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_PRIORITY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getChangeFrequency($storeId)
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_CHANGE_FREQUENCY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
