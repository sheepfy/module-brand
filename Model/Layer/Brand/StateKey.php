<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\Layer\Brand;

use Magento\Catalog\Model\Layer\Category\StateKey as CategoryStateKey;
use Magento\Catalog\Model\Layer\StateKeyInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;

class StateKey extends CategoryStateKey implements StateKeyInterface
{
    public function __construct(
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        private QueryFactory $queryFactory
    ) {
        parent::__construct($storeManager, $customerSession);
    }

    public function toString($category)
    {
        return 'B_' . $this->queryFactory->get()->getId() . '_' . parent::toString($category);
    }
}
