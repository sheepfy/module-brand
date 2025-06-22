<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\ResourceModel;

use Blacksheep\Brand\Model\BrandUrlPathGenerator;
use Blacksheep\Brand\Model\BrandUrlRewriteGenerator;
use Blacksheep\Brand\Model\Config\Source\Status;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class Brand extends AbstractDb
{
    public function __construct(
        Context $context,
        Snapshot $entitySnapshot,
        RelationComposite $entityRelationComposite,
        private ProductAttributeRepositoryInterface $attributeRepository,
        private UrlPersistInterface $urlPersist,
        private BrandUrlPathGenerator $brandUrlPathGenerator,
        $connectionName = null
    ) {
        parent::__construct(
            $context,
            $entitySnapshot,
            $entityRelationComposite,
            $connectionName
        );
    }

    protected function _construct()
    {
        $this->_init('catalog_brand_entity', 'entity_id');
    }

    protected function _beforeSave(AbstractModel $object)
    {
        $urlKey = $object->getData('url_key');
        if ($urlKey === '' || $urlKey === null) {
            $object->setData('url_key', $this->brandUrlPathGenerator->generateUrlKey($object));
        }

        if (!$this->isValidBrandUrlKey((string) $object->getData('url_key'))) {
            throw new LocalizedException(__(
                'The brand URL key contains capital letters or disallowed symbols.'
            ));
        }

        if ($object->getData('status') === null) {
            $object->setStatus(Status::STATUS_ACTIVE);
        }

        return parent::_beforeSave($object);
    }

    protected function _afterDelete(AbstractModel $object)
    {
        $value = $object->getId();
        $attribute = $this->attributeRepository->get('brand');
        $attributeId = $attribute->getId();

        $catalogProductEntityIntTable = $attribute->getBackendTable();

        $this->getConnection()->delete(
            $catalogProductEntityIntTable,
            ['attribute_id = ?' => $attributeId, '`value` = ?' => $value]
        );

        if ($object->getLogo()) {
            //phpcs:disable
            $logoPath = $object->getImagePath($object->getLogo());
            if (file_exists($logoPath)) {
                @unlink($logoPath);
            }
            //phpcs:enable
        }

        $this->urlPersist->deleteByData([
            UrlRewrite::ENTITY_ID => $object->getId(),
            UrlRewrite::ENTITY_TYPE => BrandUrlRewriteGenerator::ENTITY_TYPE,
        ]);

        return parent::_afterDelete($object);
    }

    protected function isValidBrandUrlKey(string $urlKey): bool
    {
        if (!$urlKey) {
            return true;
        }

        return (bool) preg_match('/^[a-z0-9][a-z0-9_\/-]+(\.[a-z0-9_-]+)?$/', $urlKey);
    }
}
