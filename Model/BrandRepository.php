<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model;

use Blacksheep\Brand\Api\BrandRepositoryInterface;
use Blacksheep\Brand\Api\Data\BrandInterface;
use Blacksheep\Brand\Api\Data\BrandSearchResultsInterface;
use Blacksheep\Brand\Model\ResourceModel\Brand as BrandResource;
use Blacksheep\Brand\Model\ResourceModel\Brand\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class BrandRepository implements BrandRepositoryInterface
{
    private array $cache = [];

    public function __construct(
        private BrandFactory $brandFactory,
        private BrandResource $brandResource,
        private CollectionFactory $collectionFactory,
        private BrandSearchResultsFactory $searchResultsFactory,
        private CollectionProcessorInterface $collectionProcessor
    ) {}

    /**
     * @inheritdoc
     */
    public function save(BrandInterface $brand): BrandInterface
    {
        try {
            $this->brandResource->save($brand);
            $this->cache[$brand->getId()] = $brand;
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save brand: %1', $e->getMessage()), $e);
        }

        return $this->cache[$brand->getId()];
    }

    /**
     * @inheritdoc
     */
    public function getById(int $id): BrandInterface
    {
        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }

        /** @var \Blacksheep\Brand\Model\Brand $brand */
        $brand = $this->brandFactory->create();
        $this->brandResource->load($brand, $id);

        if (!$brand->getId()) {
            throw new NoSuchEntityException(__('Brand with id "%1" does not exist.', $id));
        }

        return $this->cache[$id] = $brand;
    }

    /**
     * @inheritdoc
     */
    public function delete(BrandInterface $brand): self
    {
        try {
            $this->brandResource->delete($brand);
            unset($this->cache[$brand->getEntityId()]);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Cannot delete brand with id %1', $brand->getId()), $e);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $id): self
    {
        return $this->delete($this->getById($id));
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): BrandSearchResultsInterface
    {
        /** @var \Blacksheep\Brand\Model\ResourceModel\Brand\Collection $collection */
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var BrandInterface[] $items */
        $items = $collection->getItems();

        /** @var BrandSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($items);
        $searchResults->setTotalCount(count($items));

        foreach ($items as $item) {
            $this->cache[$item->getId()] = $item;
        }

        return $searchResults;
    }
}
