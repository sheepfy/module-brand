<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Observer;

use Blacksheep\Brand\Model\Indexer\Brand\Product\Processor as IndexProcessor;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CatalogProductDeleteCommitAfterObserver implements ObserverInterface
{
    public function __construct(
        private IndexProcessor $indexProcessor
    ) {}

    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();

        $this->indexProcessor->reindexRow($product->getId());
    }
}
