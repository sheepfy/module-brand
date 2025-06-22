<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Controller\Adminhtml\Brand;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Blacksheep_Brand::list';

    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->prepend((__('Brands')->render()));

        return $resultPage;
    }
}
