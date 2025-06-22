<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Controller\Adminhtml\Brand;

use Blacksheep\Brand\Api\BrandRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'Blacksheep_Brand::delete';

    public function __construct(
        Context $context,
        private BrandRepositoryInterface $brandRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $id = (int)$this->getRequest()->getParam('id');
        if (!$id) {
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $this->brandRepository->deleteById($id);
            $this->messageManager->addSuccessMessage(__('Brand deleted')->render());

            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
