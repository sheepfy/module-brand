<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Controller\Adminhtml\Brand;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class Upload extends Action
{
    public const ADMIN_RESOURCE = 'Blacksheep_Brand::save';

    public function __construct(
        Context $context,
        private array $uploader = []
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $type = $this->getRequest()->getParam('type');
            if (!isset($this->uploader[$type])) {
                throw new LocalizedException(__('Uploader "%1" is not defined.', $type));
            }
            $fieldName = $this->getRequest()->getParam('param_name', $type);
            $uploader = $this->uploader[$type];
            $result = $uploader->saveFileToTmpDir($fieldName);
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
