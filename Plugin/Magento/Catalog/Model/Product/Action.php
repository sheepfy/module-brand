<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Plugin\Magento\Catalog\Model\Product;

use Blacksheep\Brand\Model\Indexer\Brand\Product\Processor as IndexProcessor;
use Magento\Catalog\Model\Product\Action as Subject;

class Action
{
    public function __construct(
        private IndexProcessor $indexProcessor
    ) {}

    public function afterUpdateAttributes(Subject $subject, Subject $result, $productIds): Subject
    {
        $this->indexProcessor->reindexList(array_unique($productIds));

        return $result;
    }

    public function afterUpdateWebsites(Subject $subject, Subject $result, $productIds): Subject
    {
        $this->indexProcessor->reindexList(array_unique($productIds));

        return $result;
    }
}
