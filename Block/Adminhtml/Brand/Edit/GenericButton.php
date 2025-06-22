<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Block\Adminhtml\Brand\Edit;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

class GenericButton
{
    public function __construct(
        private UrlInterface $urlBuilder,
        private RequestInterface $request
    ) {}

    public function getBrandId(): ?int
    {
        return (int) $this->request->getParam('id') ?: null;
    }

    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->urlBuilder->getUrl($route, $params);
    }
}
