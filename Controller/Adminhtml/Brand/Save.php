<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Controller\Adminhtml\Brand;

use Blacksheep\Brand\Api\BrandRepositoryInterface;
use Blacksheep\Brand\Api\Data\BrandInterface;
use Blacksheep\Brand\Model\BrandFactory;
use Blacksheep\Brand\Model\BrandUrlPathGenerator;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'Blacksheep_Brand::save';

    public function __construct(
        Context $context,
        private DataPersistorInterface $dataPersistor,
        private DataObjectHelper $dataObjectHelper,
        private BrandFactory $brandFactory,
        private BrandRepositoryInterface $brandRepository,
        private BrandUrlPathGenerator $brandUrlPathGenerator,
        private LoggerInterface $logger,
        private array $uploader = []
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $requestData = $this->getRequest()->getPostValue();
        if (!$requestData) {
            return $resultRedirect->setPath('*/*/');
        }

        $brand = null;
        $data = $requestData['general'] ?? [];
        $id = $this->getId($data);
        if ($id) {
            try {
                $brand = $this->brandRepository->getById($id);
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This brand no longer exists.'));

                return $resultRedirect->setPath('*/*/');
            }
        }

        if (!$brand) {
            /** @var BrandInterface $brand */
            $brand = $this->brandFactory->create();
        }

        try {
            $this->prepareObject($brand, $data);
            if (!$brand->getId()) {
                $brand->setId(null);
            }
            $this->brandRepository->save($brand);

            $this->messageManager->addSuccessMessage(__('The brand was saved successfully')->render());
            $this->dataPersistor->clear('catalog_brand');
            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['id' => $brand->getId()]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (CouldNotSaveException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __(
                'Something went wrong while saving the brand.'
            )->render());

            $this->logger->error($e->getMessage());
        }

        $this->dataPersistor->set('catalog_brand', $requestData);

        return $resultRedirect->setPath('*/*/edit', ['id' => $id]);
    }

    private function getId(array $data): ?int
    {
        $id = $data['entity_id'] ?? null;

        return (int)$id ?: null;
    }

    private function prepareObject(?BrandInterface $brand, array $data): void
    {
        $id = $this->getId($data);
        if ($id) {
            $data['id'] = $id;
        }

        unset($data['created_at']);
        unset($data['updated_at']);

        foreach (['image', 'logo'] as $media) {
            $this->prepareMedia($media, $data);
        }

        $this->dataObjectHelper->populateWithArray($brand, $data, BrandInterface::class);

        $urlKey = $this->brandUrlPathGenerator->generateUrlKey($brand);
        $brand->setUrlKey($urlKey);
    }

    private function prepareMedia(string $key, array &$data): void
    {
        $media = $data[$key] ?? null;
        unset($data[$key]);
        if ($media === null) {
            $data[$key] = null;
        }

        if (is_array($media) && isset($media[0]['name'])) {
            $data[$key] = $media[0]['name'];

            // save image
            if (isset($media[0]['tmp_name'])) {
                $data[$key] = $this->uploader[$key]->moveFileFromTmp($data[$key]);
            }
        }
    }
}
