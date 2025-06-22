<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Api;

use Blacksheep\Brand\Api\Data\BrandInterface;
use Blacksheep\Brand\Api\Data\BrandSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface BrandRepositoryInterface
{
    /**
     * @param \Blacksheep\Brand\Api\Data\BrandInterface $brand
     * @return \Blacksheep\Brand\Api\Data\BrandInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(BrandInterface $brand): BrandInterface;

    /**
     * @param int $id
     * @return \Blacksheep\Brand\Api\Data\BrandInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): BrandInterface;

    /**
     * @param \Blacksheep\Brand\Api\Data\BrandInterface $brand
     * @return \Blacksheep\Brand\Api\BrandRepositoryInterface
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(BrandInterface $brand): self;

    /**
     * @param int $id
     * @return \Blacksheep\Brand\Api\BrandRepositoryInterface
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $id): self;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Blacksheep\Brand\Api\Data\BrandSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): BrandSearchResultsInterface;
}
