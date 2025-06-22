<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Block\Widget\Brand;

use Blacksheep\Brand\Api\Data\BrandInterface;
use Blacksheep\Brand\Block\Brand\Image;
use Blacksheep\Brand\Block\Brand\ImageFactory;
use Blacksheep\Brand\Helper\Image as ImageHelper;
use Blacksheep\Brand\Model\BrandUrlPathGenerator;
use Blacksheep\Brand\Model\Config\Source\Status;
use Blacksheep\Brand\Model\ResourceModel\Brand\Collection;
use Blacksheep\Brand\Model\ResourceModel\Brand\CollectionFactory;
use Magento\Catalog\Block\Product\Widget\Html\Pager;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Widget\Block\BlockInterface;
use Magento\Framework\View\Element\AbstractBlock;

class BrandsList extends Template implements BlockInterface, IdentityInterface
{
    private const DEFAULT_SHOW_PAGER = false;

    private const DEFAULT_BRANDS_PER_PAGE = 5;

    private const DEFAULT_BRANDS_COUNT = 10;

    private array $brands = [];

    private ?Pager $pager = null;

    public function __construct(
        Context $context,
        private CollectionFactory $collectionFactory,
        private BrandUrlPathGenerator $brandUrlPathGenerator,
        private ImageFactory $imageFactory,
        private ImageHelper $imageHelper,
        private SerializerInterface $serializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function _toHtml()
    {
        if (!$this->getBrands()) {
            return '';
        }

        return parent::_toHtml();
    }

    public function getTitle(): string
    {
        return $this->_getData('title');
    }

    public function showPager(): bool
    {
        if (!$this->hasData('show_pager')) {
            $this->setData('show_pager', self::DEFAULT_SHOW_PAGER);
        }

        return (bool)$this->getData('show_pager');
    }

    public function getBrandsPerPage(): int
    {
        if (!$this->hasData('brands_per_page')) {
            $this->setData('brands_per_page', ((int)$this->getData('brands_per_page')) ?: self::DEFAULT_BRANDS_PER_PAGE);
        }

        return (int)$this->_getData('brands_per_page');
    }

    public function getBrandsCount(): int
    {
        if (!$this->hasData('brands_count')) {
            $this->setData('brands_count', ((int)$this->getData('brands_count')) ?: self::DEFAULT_BRANDS_COUNT);
        }

        return (int)$this->_getData('brands_count');
    }

    public function getBrands(): array
    {
        if (!$this->brands) {
            $this->brands = $this->getBrandCollection()->getItems();
        }

        return $this->brands;
    }

    public function getBrandCollection(): Collection
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToSelect([
            BrandInterface::ENTITY_ID,
            BrandInterface::NAME,
            BrandInterface::LOGO,
            BrandInterface::IMAGE,
            BrandInterface::URL_KEY,
        ]);

        $collection->addFieldToFilter(BrandInterface::SHOW_IN_CAROUSEL, ['eq' => true]);
        $collection->addFieldToFilter(BrandInterface::STATUS, ['eq' => Status::STATUS_ACTIVE]);
        $collection->setPageSize($this->getBrandsPerPage());
        $collection->setCurPage($this->getRequest()->getParam($this->getData('page_var_name'), 1));
        $collection->addOrder(BrandInterface::PRIORITY);

        return $collection;
    }

    public function getImage(BrandInterface $brand, string $imageId, array $attributes = []): Image
    {
        $this->imageHelper->init($brand, $imageId, $attributes)->getUrl();

        return $this->imageFactory->create($brand, $imageId, $attributes);
    }

    public function getBrandUrl(BrandInterface $brand): string
    {
        return $this->getUrl() . $this->brandUrlPathGenerator->getUrlPathWithSuffix($brand);
    }

    public function getPagerHtml(): string
    {
        if (!$this->showPager() || $this->getBrandCollection()->getSize() <= $this->getBrandsPerPage()) {
            return '';
        }

        if (!$this->pager) {
            $this->pager = $this->getLayout()->createBlock(Pager::class, $this->getWidgetPagerBlockName());

            $this->pager->setUseContainer(true);
            $this->pager->setShowAmounts(true);
            $this->pager->setShowPerPage(false);
            $this->pager->setPageVarName($this->getData('page_var_name'));
            $this->pager->setLimit($this->getBrandsPerPage());
            $this->pager->setTotalLimit($this->getBrandsCount());
            $this->pager->setCollection($this->getBrandCollection());
        }
        if ($this->pager instanceof AbstractBlock) {
            return $this->pager->toHtml();
        }

        return '';
    }

    public function getCacheKeyInfo()
    {
        return [
            'BLACKSHEEP_CATALOG_BRANDS_LIST_WIDGET',
            $this->_storeManager->getStore()->getId(),
            $this->_design->getDesignTheme()->getId(),
            $this->serializer->serialize($this->getRequest()->getParams())
        ];
    }

    public function getIdentities()
    {
        $identities = [];
        /** @var \Blacksheep\Brand\Model\Brand $brand */
        foreach ($this->getBrands() as $brand) {
            if ($brand instanceof IdentityInterface) {
                $identities[] = $brand->getIdentities();
            }
        }

        $identities = array_merge(...$identities);

        return $identities ?: [\Blacksheep\Brand\Model\Brand::CACHE_TAG];
    }

    private function getWidgetPagerBlockName(): string
    {
        $pageName = $this->getData('page_var_name');
        $pagerBlockName = 'widget.brands.list.pager';

        if (!$pageName) {
            return $pagerBlockName;
        }

        return $pagerBlockName . '.' . $pageName;
    }
}
