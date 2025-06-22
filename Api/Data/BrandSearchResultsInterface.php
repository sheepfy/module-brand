<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface BrandSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @param \Blacksheep\Brand\Api\Data\BrandInterface[] $items
     * @return \Blacksheep\Brand\Api\Data\BrandSearchResultsInterface
     */
    public function setItems(array $items);

    /**
     * @return \Blacksheep\Brand\Api\Data\BrandInterface[]
     */
    public function getItems();
}
