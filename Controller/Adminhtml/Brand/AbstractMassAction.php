<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Controller\Adminhtml\Brand;

use Blacksheep\Brand\Api\BrandRepositoryInterface;
use Blacksheep\Brand\Model\Brand\Media\ConfigInterface;
use Blacksheep\Brand\Model\ResourceModel\Brand\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Ui\Component\MassAction\Filter;

abstract class AbstractMassAction extends Action
{
    protected WriteInterface $mediaDirectory;

    public function __construct(
        Context $context,
        Filesystem $filesystem,
        protected Filter $filter,
        protected CollectionFactory $collectionFactory,
        protected BrandRepositoryInterface $brandRepository,
        protected ConfigInterface $mediaConfig
    ) {
        parent::__construct($context);

        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            return $this->massAction($collection);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

            return $resultRedirect->setPath('*/*/index');
        }
    }

    abstract protected function massAction(AbstractDb $collection): ResultInterface;
}
