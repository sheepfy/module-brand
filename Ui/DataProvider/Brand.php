<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Ui\DataProvider;

use Blacksheep\Brand\Model\ResourceModel\Brand\CollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class Brand extends AbstractDataProvider
{
    private array $loadedData = [];

    private WriteInterface $mediaDirectory;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $CollectionFactory,
        Filesystem $filesystem,
        private DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $meta,
            $data
        );

        $this->collection = $CollectionFactory->create();
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    public function getData()
    {
        if ($this->loadedData) {
            return $this->loadedData;
        }

        /** @var \Blacksheep\Brand\Model\Brand[] $items */
        $items = $this->collection->getItems();
        foreach ($items as $brand) {
            $loadedData['general'] = $brand->getData();

            foreach (['image' => $brand->getImage(), 'logo' => $brand->getLogo()] as $type => $file) {
                if (!$file) {
                    continue;
                }

                $loadedData['general'][$type] = [
                    $this->processBrandImage($file, $brand->getImagePath($file), $brand->getImageUrl($file)),
                ];
            }

            $this->loadedData[$brand->getId()] = $loadedData;
        }

        $data = $this->dataPersistor->get('catalog_brand');
        if ($data) {
            /** @var \Blacksheep\Brand\Model\Brand $brand */
            $brand = $this->collection->getNewEmptyItem();
            $brand->setData($data);
            $this->loadedData[$brand->getId()] = $brand->getData();
            $this->dataPersistor->clear('catalog_brand');
        }

        return $this->loadedData;
    }

    private function processBrandImage(string $file, string $path, string $url): array
    {
        try {
            $fileSize = $this->getFileSize($path);
            $formattedFileSize = $this->getFormattedFileSize($fileSize);
            $fileExtension = $this->getFileExtension($path);
        } catch (\Exception $e) {
            return [];
        }

        return [
            'name' => $file,
            'url' => $url,
            'type' => 'image/' . $fileExtension,
            'size' => $fileSize,
            'path' => $path,
            'file' => $file,
            'formatted_file_size' => $formattedFileSize,
        ];
    }

    private function getFileSize(string $file): int
    {
        return (int) $this->mediaDirectory->stat($file)['size'] ?? 0;
    }

    public function getFileExtension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    public function getFormattedFileSize(int $fileSize): string
    {
        if ($fileSize <= 2048) {
            return $fileSize . ' b';
        }

        if ($fileSize <= 2097152) {
            return round($fileSize / 1024, 2) . ' KB';
        }

        return round($fileSize / 1048576, 2) . ' MB';
    }
}
