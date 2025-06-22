<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model;

use Blacksheep\Brand\Api\Data\BrandSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class BrandSearchResults extends SearchResults implements BrandSearchResultsInterface
{
}
