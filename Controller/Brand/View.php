<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Controller\Brand;

use Blacksheep\Brand\Api\BrandRepositoryInterface;
use Blacksheep\Brand\Api\Data\BrandInterface;
use Blacksheep\Brand\Model\BrandUrlPathGenerator;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class View implements HttpGetActionInterface
{
    public function __construct(
        private RequestInterface $request,
        private RedirectInterface $redirect,
        private ResponseInterface $response,
        private ResultFactory $resultFactory,
        private Resolver $layerResolver,
        private ManagerInterface $eventManager,
        private CatalogSession $catalogSession,
        private BrandRepositoryInterface $brandRepository,
        private BrandUrlPathGenerator $brandUrlPathGenerator,
        private LoggerInterface $logger
    ) {}

    private function initBrand(): ?BrandInterface
    {
        if (!$id = (int) $this->request->getParam('brand_id')) {
            return null;
        }

        try {
            $brand = $this->brandRepository->getById($id);
        } catch (NoSuchEntityException $e) {
            return null;
        }

        if (!$brand->isActive()) {
            return null;
        }

        $this->catalogSession->setLastVisitedBrandId($brand->getId());

        try {
            $this->eventManager->dispatch('catalog_controller_brand_init_after', [
                'brand' => $brand,
                'controller_action' => $this
            ]);
        } catch (\Exception $e) {
            $this->logger->critical($e);

            return null;
        }

        return $brand;
    }

    public function execute()
    {
        if (!$this->request->getParam('___from_store') && $this->request->getParam(self::PARAM_NAME_URL_ENCODED)) {
            return $this->redirect($this->redirect->getRedirectUrl());
        }

        $brand = $this->initBrand();
        if ((!$brand || !$brand->getId()) && !$this->response->isRedirect()) {
            return $this->forward('noroute');
        }

        $this->layerResolver->create('brand');

        $type = 'layered';

        /** @var \Magento\Framework\View\Result\Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->addPageLayoutHandles(['type' => $type], null, false);
        $page->addPageLayoutHandles(['id' => $brand->getId()]);

        $config = $page->getConfig();
        $config->addBodyClass('page-products');
        $config->addBodyClass('brandpath-' . $this->brandUrlPathGenerator->getUrlPath($brand));
        $config->addBodyClass('brand-' . $brand->getUrlKey());

        return $page;
    }

    private function redirect(string $url): ResultInterface
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setUrl($url);
    }

    private function forward(string $action): ResultInterface
    {
        /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
        $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);

        return $resultForward->forward($action);
    }
}
