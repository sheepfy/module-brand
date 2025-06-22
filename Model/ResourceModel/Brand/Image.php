<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\ResourceModel\Brand;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Query\BatchIteratorInterface;
use Magento\Framework\DB\Query\Generator;
use Magento\Framework\DB\Select;

class Image
{
    public function __construct(
        private Generator $batchQueryGenerator,
        private ResourceConnection $resourceConnection,
        private int $batchSize = 100
    ) {}

    public function getAllBrandImages(): \Generator
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            ['sub' => $this->getImagesUnionSelect()],
            'filepath'
        )->where('filepath IS NOT NULL');

        $batchSelectIterator = $this->batchQueryGenerator->generate(
            'filepath',
            $select,
            $this->batchSize,
            BatchIteratorInterface::NON_UNIQUE_FIELD_ITERATOR
        );

        foreach ($batchSelectIterator as $select) {
            foreach ($connection->fetchAll($select) as $key => $value) {
                yield $key => $value;
            }
        }
    }

    public function getCountAllBrandImages(): int
    {
        $connection = $this->resourceConnection->getConnection();
        $count = 0;
        $selects = $this->getImagesSelect();
        /** @var Select $select */
        foreach ($selects as $image => $select) {
            $select->reset('columns');
            $select->reset('distinct');
            $select->columns(new \Zend_Db_Expr('count(distinct ' . $image . ')'));

            $count += (int)$connection->fetchOne($select);
        }

        return $count;
    }

    private function getImagesUnionSelect(): Select
    {
        return $this->resourceConnection->getConnection()->select()->union($this->getImagesSelect());
    }

    private function getImagesSelect(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $selects = [];
        foreach (['image', 'logo'] as $image) {
            $selects[$image] = $connection->select()->distinct()->from(
                ['brand' => $connection->getTableName('catalog_brand_entity')],
                "{$image} as filepath"
            );
        }

        return $selects;
    }
}
