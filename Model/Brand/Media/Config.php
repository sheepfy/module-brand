<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\Brand\Media;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config implements ConfigInterface
{
    public function __construct(
        private StoreManagerInterface $storeManager
    ) {}

    public function getBaseMediaPathAddition(): string
    {
        return 'catalog/brand';
    }

    public function getBaseMediaUrlAddition(): string
    {
        return 'catalog/brand';
    }

    public function getBaseMediaPath(): string
    {
        return 'catalog/brand';
    }

    public function getBaseMediaUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/brand';
    }

    public function getBaseTmpMediaPath(): string
    {
        return 'tmp/' . $this->getBaseMediaPathAddition();
    }

    public function getBaseTmpMediaUrl(): string
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        return $baseUrl . 'tmp/' . $this->getBaseMediaUrlAddition();
    }

    public function getMediaUrl(string $file): string
    {
        return $this->getBaseMediaUrl() . '/' . $this->prepareFile($file);
    }

    public function getMediaPath(string $file): string
    {
        return $this->getBaseMediaPath() . '/' . $this->prepareFile($file);
    }

    public function getTmpMediaUrl(string $file): string
    {
        return $this->getBaseTmpMediaUrl() . '/' . $this->prepareFile($file);
    }

    public function getTmpMediaShortUrl(string $file): string
    {
        return 'tmp/' . $this->getBaseMediaUrlAddition() . '/' . $this->prepareFile($file);
    }

    public function getMediaShortUrl(string $file): string
    {
        return $this->getBaseMediaUrlAddition() . '/' . $this->prepareFile($file);
    }

    public function getTmpMediaPath(string $file): string
    {
        return $this->getBaseTmpMediaPath() . '/' . $this->prepareFile($file);
    }

    private function prepareFile(string $file): string
    {
        return ltrim(str_replace('\\', '/', $file), '/');
    }

    public function getMediaAttributeCodes(): array
    {
        return ['logo', 'image'];
    }
}
