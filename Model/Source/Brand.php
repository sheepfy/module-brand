<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\Source;

use Blacksheep\Brand\Model\ResourceModel\Brand\CollectionFactory;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\DB\Ddl\Table;
use Zend_Db_Select as Select;

class Brand extends AbstractSource
{
    public function __construct(
        private CollectionFactory $collectionFactory,
        private Attribute $eavEntityAttribute
    ) {
    }

    public function getAllOptions(bool $withEmpty = true)
    {
        if (!$this->_options) {
            $this->_options = $this->toOptionArray();
        }

        if ($withEmpty) {
            $none = [['value' => '', 'label' => __('None')->render()]];
            if (!$this->_options) {
                return $none;
            } else {
                return array_merge($none, $this->_options);
            }
        }

        return $this->_options;
    }

    public function getOptionText($value)
    {
        $options = $this->getAllOptions();
        foreach ($options as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }

    public function toOptionArray()
    {
        /** @var \Blacksheep\Brand\Model\ResourceModel\Brand\Collection $brandCollection */
        $brandCollection = $this->collectionFactory->create();
        $select = $brandCollection->getSelect()
            ->reset(Select::COLUMNS)
            ->columns(['entity_id', 'name'])
            ->order('name');

        $options = [];
        foreach ($brandCollection->getConnection()->fetchAll($select) as $brand) {
            $options[] = ['value' => $brand['entity_id'], 'label' => $brand['name']];
        }

        return $options;
    }

    public function getFlatColumns()
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();

        return [
            $attributeCode => [
                'unsigned' => true,
                'default' => null,
                'extra' => null,
                'type' => Table::TYPE_SMALLINT,
                'nullable' => true,
                'comment' => 'Catalog Product Brand ' . $attributeCode . ' column',
            ],
        ];
    }

    public function getFlatIndexes()
    {
        $indexes = [];

        $index = 'IDX_' . strtoupper($this->getAttribute()->getAttributeCode());
        $indexes[$index] = ['type' => 'index', 'fields' => [$this->getAttribute()->getAttributeCode()]];

        return $indexes;
    }

    public function getFlatUpdateSelect($store)
    {
        return $this->eavEntityAttribute->getFlatUpdateSelect($this->getAttribute(), $store);
    }
}
