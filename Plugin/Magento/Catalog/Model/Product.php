<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Plugin\Magento\Catalog\Model;

use Blacksheep\Brand\Model\Brand;
use Magento\Catalog\Model\Product as Subject;

class Product
{
    public function afterGetIdentities(Subject $subject, array $result): array
    {
        $originalBrandId = (int)$subject->getOrigData('brand');
        $brandId = (int)$subject->getData('brand');

        if ($originalBrandId !== $brandId || $brandId) {
            $result[] = Brand::CACHE_PRODUCT_BRAND_TAG . '_' . $brandId;
        }

        return $result;
    }
}
