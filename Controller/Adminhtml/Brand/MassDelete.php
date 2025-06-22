<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Controller\Adminhtml\Brand;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Controller\ResultInterface;

class MassDelete extends AbstractMassAction
{
    public const ADMIN_RESOURCE = 'Blacksheep_Brand::delete';

    protected function massAction(AbstractDb $collection): ResultInterface
    {
        $count = 0;

        /** @var \Blacksheep\Brand\Api\Data\BrandInterface $brand */
        foreach ($collection as $brand) {
            $this->brandRepository->delete($brand);

            try {
                foreach ([$brand->getLogo(), $brand->getImage()] as $file) {
                    if (!$file) {
                        continue;
                    }

                    $this->mediaDirectory->delete($this->mediaConfig->getTmpMediaPath($file));
                }
            } catch (\Exception $e) { //phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
            }

            $count++;
        }

        if ($count) {
            $this->messageManager->addSuccessMessage(__(
                'A total of %1 record(s) were deleted.',
                $count
            )->render());
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/');

        return $resultRedirect;
    }
}
