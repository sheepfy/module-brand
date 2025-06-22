<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model;

use Blacksheep\Brand\Api\Data\BrandInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManager;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class Brand extends AbstractExtensibleModel implements BrandInterface, IdentityInterface
{
    public const CACHE_TAG = 'cat_b';

    public const CACHE_PRODUCT_BRAND_TAG = 'cat_b_p';

    protected $_eventPrefix = 'catalog_brand';

    protected $_eventObject = 'brand';

    protected $_cacheTag = self::CACHE_TAG;

    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        private DirectoryList $directoryList,
        private StoreManager $storeManager,
        private UrlInterface $urlBuilder,
        private UrlFinderInterface $urlFinder,
        private FilterManager $filterManager,
        private ProductCollectionFactory $productCollectionFactory,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(\Blacksheep\Brand\Model\ResourceModel\Brand::class);
    }

    /**
     * @inheritdoc
     */
    public function setEntityId($entityId)
    {
        $this->setData(self::ENTITY_ID, $entityId);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getEntityId(): ?int
    {
        return (int) $this->_getData(self::ENTITY_ID) ?: null;
    }

    /**
     * @inheritdoc
     */
    public function setName(string $name): self
    {
        $this->setData(self::NAME, $name);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return (string) $this->_getData(self::NAME);
    }

    /**
     * @inheritdoc
     */
    public function setPageTitle(?string $pageTitle): self
    {
        $this->setData(self::PAGE_TITLE, $pageTitle);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPageTitle(): ?string
    {
        return $this->_getData(self::PAGE_TITLE);
    }

    /**
     * @inheritdoc
     */
    public function setDescription(?string $description): self
    {
        $this->setData(self::DESCRIPTION, $description);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): ?string
    {
        return $this->_getData(self::DESCRIPTION);
    }

    /**
     * @inheritdoc
     */
    public function setUrlKey(string $urlKey): self
    {
        $this->setData(self::URL_KEY, $urlKey);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUrlKey(): string
    {
        return (string) $this->_getData(self::URL_KEY);
    }

    /**
     * @inheritdoc
     */
    public function setImage(?string $image = null): self
    {
        $this->setData(self::IMAGE, $image);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getImage(): ?string
    {
        return $this->_getData(self::IMAGE);
    }

    /**
     * @inheritdoc
     */
    public function setLogo(?string $logo = null): self
    {
        $this->setData(self::LOGO, $logo);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getLogo(): ?string
    {
        return $this->_getData(self::LOGO);
    }

    /**
     * @inheritdoc
     */
    public function setStatus(int $status): self
    {
        $this->setData(self::STATUS, $status);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): int
    {
        return (int) $this->_getData(self::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setPriority(int $priority): self
    {
        $this->setData(self::PRIORITY, $priority);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPriority(): int
    {
        return (int) $this->_getData(self::PRIORITY);
    }

    /**
     * @inheritdoc
     */
    public function setShowInCarousel(bool $showInCarousel): self
    {
        $this->setData(self::SHOW_IN_CAROUSEL, $showInCarousel);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getShowInCarousel(): bool
    {
        return (bool) $this->_getData(self::SHOW_IN_CAROUSEL);
    }

    /**
     * @inheritdoc
     */
    public function setMetaTitle(?string $metaTitle): self
    {
        $this->setData(self::META_TITLE, $metaTitle);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMetaTitle(): ?string
    {
        return $this->_getData(self::META_TITLE);
    }

    /**
     * @inheritdoc
     */
    public function setMetaKeywords(?string $metaKeywords): self
    {
        $this->setData(self::META_KEYWORDS, $metaKeywords);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMetaKeywords(): ?string
    {
        return $this->_getData(self::META_KEYWORDS);
    }

    /**
     * @inheritdoc
     */
    public function setMetaDescription(?string $metaDescription): self
    {
        $this->setData(self::META_DESCRIPTION, $metaDescription);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMetaDescription(): ?string
    {
        return $this->_getData(self::META_DESCRIPTION);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(string $createdAt): self
    {
        $this->setData(self::CREATED_AT, $createdAt);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): string
    {
        return (string) $this->_getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(string $updatedAt): self
    {
        $this->setData(self::UPDATED_AT, $updatedAt);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): string
    {
        return (string) $this->_getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(
        \Blacksheep\Brand\Api\Data\BrandExtensionInterface $extensionAttributes
    ): self {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes(): \Blacksheep\Brand\Api\Data\BrandExtensionInterface
    {
        /** @var \Blacksheep\Brand\Api\Data\BrandExtensionInterface $extensionAttributes */
        $extensionAttributes = $this->_getExtensionAttributes();
        if (!$extensionAttributes) {
            $extensionAttributes = $this->extensionAttributesFactory->create(BrandInterface::class);
        }

        return $extensionAttributes;
    }

    /**
     * @inheritdoc
     */
    public function getIdentities(): array
    {
        $identities = [
            self::CACHE_TAG . '_' . $this->getId(),
        ];

        if ($this->hasDataChanges()) {
            $identities[] = self::CACHE_PRODUCT_BRAND_TAG . '_' . $this->getId();
        }

        if (!$this->getId() || $this->isObjectNew() || $this->isDeleted()) {
            $identities[] = self::CACHE_TAG;
            $identities[] = self::CACHE_PRODUCT_BRAND_TAG . '_' . $this->getId();
        }

        return array_unique($identities);
    }

    public function isActive(): bool
    {
        return $this->getStatus() === \Blacksheep\Brand\Model\Config\Source\Status::STATUS_ACTIVE;
    }

    public function getImageUrl(string $fileName): string
    {
        $store = $this->storeManager->getStore();

        return $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/brand' . $fileName;
    }

    public function getImagePath(string $fileName): string
    {
        return $this->directoryList->getPath('media') . '/catalog/brand' . $fileName;
    }

    private function getRequestPath(): string
    {
        return (string) $this->_getData('request_path');
    }

    public function getUrl(): string
    {
        $url = $this->_getData('url');
        if ($url) {
            return $url;
        }

        if ($this->hasData('request_path') && $this->getRequestPath() != '') {
            $this->setData('url', $this->urlBuilder->getDirectUrl($this->getRequestPath()));

            return $this->getData('url');
        }

        $rewrite = $this->urlFinder->findOneByData([
            UrlRewrite::ENTITY_ID => $this->getId(),
            UrlRewrite::ENTITY_TYPE => BrandUrlRewriteGenerator::ENTITY_TYPE,
            //UrlRewrite::STORE_ID => $this->getStoreId(),
        ]);

        if ($rewrite) {
            $this->setData('url', $this->urlBuilder->getDirectUrl($rewrite->getRequestPath()));

            return $this->getData('url');
        }

        $this->setData('url', $this->getBrandIdUrl());

        return $this->getData('url');
    }

    public function getBrandIdUrl(): string
    {
        $urlKey = $this->getUrlKey() ?
            $this->getUrlKey() : $this->filterManager->translitUrl($this->getName());

        return $this->urlBuilder->getUrl('*/*/view', ['s' => $urlKey, 'id' => $this->getId()]);
    }

    public function getProductCollection(): ProductCollection
    {
        /** @var ProductCollection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId($this->getStoreId());
        $collection->addAttributeToFilter('brand', $this->getId());

        return $collection;
    }
}
