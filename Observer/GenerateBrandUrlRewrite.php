<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Observer;

use Blacksheep\Brand\Model\BrandUrlRewriteGenerator;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class GenerateBrandUrlRewrite implements ObserverInterface
{
    public function __construct(
        private UrlPersistInterface $urlPersist,
        private BrandUrlRewriteGenerator $brandUrlRewriteGenerator
    ) {}

    public function execute(Observer $observer)
    {
        $brand = $observer->getEvent()->getBrand();

        if ($brand->dataHasChangedFor('url_key')) {
            $urls = $this->brandUrlRewriteGenerator->generate($brand);
            $this->urlPersist->deleteByData([
                UrlRewrite::ENTITY_ID => $brand->getId(),
                UrlRewrite::ENTITY_TYPE => BrandUrlRewriteGenerator::ENTITY_TYPE,
            ]);
            $this->urlPersist->replace($urls);
        }
    }
}
