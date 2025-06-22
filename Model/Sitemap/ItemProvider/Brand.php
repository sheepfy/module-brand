<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\Sitemap\ItemProvider;

use Blacksheep\Brand\Model\ResourceModel\Brand\Sitemap\BrandFactory as SitemapBrandFactory;
use Magento\Sitemap\Model\ItemProvider\ConfigReaderInterface;
use Magento\Sitemap\Model\ItemProvider\ItemProviderInterface;
use Magento\Sitemap\Model\SitemapItemInterfaceFactory;

class Brand implements ItemProviderInterface
{
    public function __construct(
        private ConfigReaderInterface $configReader,
        private SitemapItemInterfaceFactory $itemFactory,
        private SitemapBrandFactory $sitemapBrandFactory
    ) {}

    public function getItems($storeId)
    {
        /** @var \Blacksheep\Brand\Model\ResourceModel\Brand\Sitemap\Brand $sitemapBrand */
        $sitemapBrand = $this->sitemapBrandFactory->create();
        $collection = $sitemapBrand->getCollection((int)$storeId);

        return array_map(function ($item) use ($storeId) {
            return $this->itemFactory->create([
                'url' => $item->getUrl(),
                'updatedAt' => $item->getUpdatedAt(),
                'images' => $item->getImages(),
                'priority' => $this->configReader->getPriority($storeId),
                'changeFrequency' => $this->configReader->getChangeFrequency($storeId),
            ]);
        }, $collection);
    }
}
