<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Controller\Adminhtml\Brand;

use Blacksheep\Brand\Api\BrandRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'Blacksheep_Brand::save';

    public function __construct(
        Context $context,
        private BrandRepositoryInterface $brandRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        if (!$id) {
            $resultPage = $this->initPage();
            $resultPage->addBreadcrumb(__('New Brand'), __('New Brand')->render());
            $resultPage->getConfig()->getTitle()->prepend(__('New Brand')->render());

            return $resultPage;
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        try {
            $this->brandRepository->getById($id);
            $resultPage = $this->initPage();
            $resultPage->addBreadcrumb(__('Edit Brand'), __('Edit Brand'));
            $resultPage->getConfig()->getTitle()->prepend('Brand');

            return $resultPage;
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addExceptionMessage($e, __(
                'The brand with ID "%1" does not exist.',
                $id
            )->render());

            return $resultRedirect->setPath('*/*/index');
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __(
                'Something went wrong while editing the brand.'
            )->render());

            return $resultRedirect->setPath('*/*/index');
        }
    }

    private function initPage(): ResultInterface
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Blacksheep_Brand::brands');
        $resultPage->addBreadcrumb(__('Catalog')->render(), __('Catalog')->render());
        $resultPage->addBreadcrumb(__('Brands')->render(), __('Brands')->render());

        return $resultPage;
    }
}
