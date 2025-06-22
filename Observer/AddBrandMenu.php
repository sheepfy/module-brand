<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Observer;

use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class AddBrandMenu implements ObserverInterface
{
    public function __construct(
        private UrlInterface $urlBuilder,
        private UrlFinderInterface $urlFinder,
        private StoreManagerInterface $storeManager
    ) {}

    public function execute(Observer $observer)
    {
        try {
            /** @var Node $menu */
            $menu = $observer->getMenu();

            $data = $this->getMenuItemData();

            $node = new Node($data, 'id', $menu->getTree(), $menu);
            $node->setClass('brands-tab');

            $menu->addChild($node);
        } catch (\Exception $e) {
        }
    }

    private function getMenuItemData(): array
    {
        $targetPath = 'catalog/brand/list';
        $rewrite = $this->getRewrite($targetPath, (int) $this->storeManager->getStore()->getId());

        return [
            'name' => __('Brands')->render(),
            'id' => 'brands-node',
            'url' => $rewrite ?
                $this->urlBuilder->getDirectUrl($rewrite->getRequestPath()) : $this->urlBuilder->getUrl($targetPath),
            'has_active' => false,
            'is_active' => false,
            'is_category' => false,
            'is_parent_active' => true,
        ];
    }


    private function getRewrite(string $targetPath, int $storeId): ?UrlRewrite
    {
        return $this->urlFinder->findOneByData([
            UrlRewrite::TARGET_PATH => ltrim($targetPath, '/'),
            UrlRewrite::STORE_ID => $storeId,
        ]);
    }
}
