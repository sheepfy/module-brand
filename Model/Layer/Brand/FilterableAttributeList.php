<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\Layer\Brand;

class FilterableAttributeList extends \Magento\Catalog\Model\Layer\Category\FilterableAttributeList
{
    protected function _prepareAttributeCollection($collection)
    {
        $collection = parent::_prepareAttributeCollection($collection);
        $collection->addFieldToFilter('attribute_code', ['neq' => 'brand']);

        return $collection;
    }
}
