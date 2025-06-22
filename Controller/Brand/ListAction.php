<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Controller\Brand;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

class ListAction implements HttpGetActionInterface
{
    public function __construct(
        private ResultFactory $resultFactory,
    ) {}

    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set((__('Brands')->render()));

        return $resultPage;
    }
}
