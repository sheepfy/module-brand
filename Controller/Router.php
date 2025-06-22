<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Controller;

use Blacksheep\Brand\Model\BrandFactory;
use Blacksheep\Brand\Model\BrandUrlPathGenerator;
use Blacksheep\Brand\Model\ResourceModel\Brand as BrandResource;
use Magento\Framework\App\Action\Forward;
use Magento\Framework\App\Action\Redirect;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\RouterInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Url;
use Magento\Store\Model\StoreManagerInterface;

class Router implements RouterInterface
{
    public function __construct(
        private ActionFactory $actionFactory,
        private ResponseInterface $response,
        private ManagerInterface $eventManager,
        private BrandFactory $brandFactory,
        private BrandResource $brandResource,
        private BrandUrlPathGenerator $brandUrlPathGenerator,
        private StoreManagerInterface $storeManager
    ) {}

    public function match(RequestInterface $request)
    {
        $pathInfo = trim($request->getPathInfo(), '/');

        $condition = new DataObject(['url_key' => $pathInfo, 'continue' => true]);
        $this->eventManager->dispatch('brand_controller_router_match_before', [
            'router' => $this,
            'condition' => $condition
        ]);

        $urlKey = explode('.', $pathInfo);

        /** @var \Blacksheep\Brand\Model\Brand $brand */
        $brand = $this->brandFactory->create();
        $this->brandResource->load($brand, $urlKey[0], 'url_key');

        if (!$brand->getId()) {
            return null;
        }

        $storeId = (int) $this->storeManager->getStore()->getId();
        $urlKeyWithSuffix = $this->brandUrlPathGenerator->getUrlPathWithSuffix($brand, $storeId);

        if ($pathInfo !== $urlKeyWithSuffix) {
            $this->response->setRedirect($urlKeyWithSuffix, 301);
            $request->setDispatched();

            return $this->actionFactory->create(Redirect::class);
        }

        $request->setModuleName('catalog');
        $request->setControllerName('brand');
        $request->setActionName('view');
        $request->setParam('id', $brand->getId());
        $request->setAlias(Url::REWRITE_REQUEST_PATH_ALIAS, $pathInfo);
        $request->setDispatched();

        return $this->actionFactory->create(Forward::class);
    }
}
