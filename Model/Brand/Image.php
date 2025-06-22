<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\Brand;

use Blacksheep\Brand\Model\Brand\Image\NotLoadInfoImageException;
use Blacksheep\Brand\Model\Brand\Image\ParamsBuilder;
use Blacksheep\Brand\Model\Brand\Media\Config;
use Blacksheep\Brand\Model\View\Asset\ImageFactory;
use Blacksheep\Brand\Model\View\Asset\PlaceholderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Image\Factory;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Asset\LocalInterface as AssetLocalInterface;
use Magento\Framework\View\FileSystem as ViewFileSystem;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Store\Model\StoreManagerInterface;

class Image extends \Magento\Framework\Model\AbstractModel
{
    public const XML_PATH_JPEG_QUALITY = 'system/upload_configuration/jpeg_quality';

    private $width;

    private $height;

    private $keepAspectRatio = true;

    private $keepFrame = true;

    private $keepTransparency = true;

    private $constrainOnly = true;

    private $backgroundColor = [255, 255, 255];

    private $baseFile;

    private $isBaseFilePlaceholder;

    private $newFile;

    private $processor;

    private $destinationSubdir;

    private $angle;

    private $watermarkFile;

    private $watermarkPosition;

    private $watermarkWidth;

    private $watermarkHeight;

    private $watermarkImageOpacity = 70;

    private WriteInterface $mediaDirectory;

    private ?AssetLocalInterface $imageAsset = null;

    private string $cachePrefix = 'IMG_INFO';

    public function __construct(
        Context $context,
        Registry $registry,
        Filesystem $filesystem,
        private StoreManagerInterface $storeManager,
        private Config $catalogBrandMediaConfig,
        private Database $coreFileStorageDatabase,
        private Factory $imageFactory,
        private ViewFileSystem $viewFileSystem,
        private ImageFactory $viewAssetImageFactory,
        private PlaceholderFactory $viewAssetPlaceholderFactory,
        private ScopeConfigInterface $scopeConfig,
        private SerializerInterface $serializer,
        private ParamsBuilder $paramsBuilder,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    public function setWidth($width): void
    {
        $this->width = $width;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function setHeight($height): void
    {
        $this->height = $height;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function getQuality()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_JPEG_QUALITY);
    }

    public function setKeepAspectRatio($keep): void
    {
        $this->keepAspectRatio = $keep && $keep !== 'false';
    }

    public function setKeepFrame($keep): void
    {
        $this->keepFrame = $keep && $keep !== 'false';
    }

    public function setKeepTransparency($keep): void
    {
        $this->keepTransparency = $keep && $keep !== 'false';
    }

    public function setConstrainOnly($flag): void
    {
        $this->constrainOnly = $flag && $flag !== 'false';
    }

    public function setBackgroundColor(array $rgbArray): void
    {
        $this->backgroundColor = $rgbArray;
    }

    public function setSize($size): void
    {
        // determine width and height from string
        list($width, $height) = explode('x', strtolower((string)$size), 2);
        foreach (['width', 'height'] as $wh) {
            ${$wh} = (int)${$wh};
            if (empty(${$wh})) {
                ${$wh} = null;
            }
        }

        // set sizes
        $this->setWidth($width);
        $this->setHeight($height);
    }

    public function setBaseFile($file): void
    {
        $this->isBaseFilePlaceholder = false;

        $this->imageAsset = $this->viewAssetImageFactory->create([
            'miscParams' => $this->getMiscParams(),
            'filePath' => $file,
        ]);
        if ($file == 'no_selection' || !$this->fileExists($this->imageAsset->getSourceFile())) {
            $this->isBaseFilePlaceholder = true;
            $this->imageAsset = $this->viewAssetPlaceholderFactory->create([
                'type' => $this->getDestinationSubdir(),
            ]);
        }

        $this->baseFile = $this->imageAsset->getSourceFile();
    }

    public function getBaseFile()
    {
        return $this->baseFile;
    }

    public function getNewFile()
    {
        return $this->newFile;
    }

    public function isBaseFilePlaceholder(): bool
    {
        return (bool)$this->isBaseFilePlaceholder;
    }

    public function setImageProcessor($processor): void
    {
        $this->processor = $processor;
    }

    public function getImageProcessor()
    {
        if (!$this->processor) {
            $filename = $this->getBaseFile() ? $this->mediaDirectory->getAbsolutePath($this->getBaseFile()) : null;
            $this->processor = $this->imageFactory->create($filename);
        }
        $this->processor->keepAspectRatio($this->keepAspectRatio);
        $this->processor->keepFrame($this->keepFrame);
        $this->processor->keepTransparency($this->keepTransparency);
        $this->processor->constrainOnly($this->constrainOnly);
        $this->processor->backgroundColor($this->backgroundColor);
        $this->processor->quality($this->getQuality());

        return $this->processor;
    }

    public function resize(): void
    {
        if ($this->getWidth() === null && $this->getHeight() === null) {
            return;
        }

        $this->getImageProcessor()->resize($this->width, $this->height);
    }

    public function rotate($angle): void
    {
        $angle = (int) $angle;
        $this->getImageProcessor()->rotate($angle);
    }

    public function setAngle($angle): void
    {
        $this->angle = $angle;
    }

    public function setWatermark(
        $file,
        $position = null,
        $size = null,
        $width = null,
        $height = null,
        $opacity = null
    ): void {
        if ($this->isBaseFilePlaceholder || !$file) {
            return;
        }

        $this->setWatermarkFile($file);

        if ($position) {
            $this->setWatermarkPosition($position);
        }
        if ($size) {
            $this->setWatermarkSize($size);
        }
        if ($width) {
            $this->setWatermarkWidth($width);
        }
        if ($height) {
            $this->setWatermarkHeight($height);
        }
        if ($opacity) {
            $this->setWatermarkImageOpacity($opacity);
        }
        $filePath = $this->getWatermarkFilePath();

        if ($filePath) {
            $imagePreprocessor = $this->getImageProcessor();
            $imagePreprocessor->setWatermarkPosition($this->getWatermarkPosition());
            $imagePreprocessor->setWatermarkImageOpacity($this->getWatermarkImageOpacity());
            $imagePreprocessor->setWatermarkWidth($this->getWatermarkWidth());
            $imagePreprocessor->setWatermarkHeight($this->getWatermarkHeight());
            $imagePreprocessor->watermark($filePath);
        }
    }

    public function saveFile(): void
    {
        if ($this->isBaseFilePlaceholder) {
            return;
        }
        $filename = $this->getBaseFile() ? $this->imageAsset->getPath() : null;
        $this->getImageProcessor()->save($filename);
        $this->coreFileStorageDatabase->saveFile($filename);
    }

    public function getUrl(): string
    {
        return $this->imageAsset->getUrl();
    }

    public function setDestinationSubdir($dir): void
    {
        $this->destinationSubdir = $dir;
    }

    public function getDestinationSubdir()
    {
        return $this->destinationSubdir;
    }

    public function isCached(): bool
    {
        $path = $this->imageAsset->getPath();

        return is_array($this->loadImageInfoFromCache($path)) || $this->mediaDirectory->isExist($path);
    }

    public function setWatermarkFile($file): void
    {
        $this->watermarkFile = $file;
    }

    public function getWatermarkFile()
    {
        return $this->watermarkFile;
    }

    protected function getWatermarkFilePath()
    {
        $filePath = false;

        if (!($file = $this->getWatermarkFile())) {
            return $filePath;
        }
        $baseDir = $this->catalogBrandMediaConfig->getBaseMediaPath();

        $candidates = [
            $baseDir . '/watermark/stores/' . $this->storeManager->getStore()->getId() . $file,
            $baseDir . '/watermark/websites/' . $this->storeManager->getWebsite()->getId() . $file,
            $baseDir . '/watermark/default/' . $file,
            $baseDir . '/watermark/' . $file,
        ];
        foreach ($candidates as $candidate) {
            if ($this->mediaDirectory->isExist($candidate)) {
                $filePath = $this->mediaDirectory->getAbsolutePath($candidate);
                break;
            }
        }
        if (!$filePath) {
            $filePath = $this->viewFileSystem->getStaticFileName($file);
        }

        return $filePath;
    }

    public function setWatermarkPosition($position): void
    {
        $this->watermarkPosition = $position;
    }

    public function getWatermarkPosition()
    {
        return $this->watermarkPosition;
    }

    public function setWatermarkImageOpacity($imageOpacity): void
    {
        $this->watermarkImageOpacity = $imageOpacity;
    }

    public function getWatermarkImageOpacity()
    {
        return $this->watermarkImageOpacity;
    }

    public function setWatermarkSize(array $size): void
    {
        if (isset($size['width']) && $size['height']) {
            $this->setWatermarkWidth($size['width']);
            $this->setWatermarkHeight($size['height']);
        }
    }

    public function setWatermarkWidth($width): void
    {
        $this->watermarkWidth = $width;
    }

    public function getWatermarkWidth()
    {
        return $this->watermarkWidth;
    }

    public function setWatermarkHeight($height): void
    {
        $this->watermarkHeight = $height;
    }

    public function getWatermarkHeight()
    {
        return $this->watermarkHeight;
    }

    public function clearCache(): void
    {
        $directory = $this->catalogBrandMediaConfig->getBaseMediaPath() . '/cache';
        try {
            $this->mediaDirectory->delete($directory);
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
        } catch (FileSystemException $e) {
        }

        $this->coreFileStorageDatabase->deleteFolder($this->mediaDirectory->getAbsolutePath($directory));
        $this->clearImageInfoFromCache();
    }

    protected function fileExists($filename): bool
    {
        if ($this->mediaDirectory->isFile($filename)) {
            return true;
        }

        return $this->coreFileStorageDatabase->saveFileToFilesystem(
            $this->mediaDirectory->getAbsolutePath($filename)
        );
    }

    public function getResizedImageInfo(): array
    {
        try {
            $image = null;
            if ($this->isBaseFilePlaceholder()) {
                $image = $this->imageAsset->getSourceFile();
            } else {
                $image = $this->imageAsset->getPath();
            }

            $imageProperties = $this->getImageSize($image);

            return $imageProperties;
        } finally {
            if (empty($imageProperties)) {
                throw new NotLoadInfoImageException(__('Can\'t get information about the picture: %1', $image));
            }
        }
    }

    private function getMiscParams(): array
    {
        return $this->paramsBuilder->build([
            'type' => $this->getDestinationSubdir(),
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
            'frame' => $this->keepFrame,
            'constrain' => $this->constrainOnly,
            'aspect_ratio' => $this->keepAspectRatio,
            'transparency' => $this->keepTransparency,
            'background' => $this->backgroundColor,
            'angle' => $this->angle,
            'quality' => $this->getQuality()
        ]);
    }

    private function getImageSize(string $imagePath): array
    {
        $imageInfo = $this->loadImageInfoFromCache($imagePath);
        if (!isset($imageInfo['size'])) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $imageSize = getimagesize($imagePath);
            $this->saveImageInfoToCache(['size' => $imageSize], $imagePath);
            return $imageSize;
        } else {
            return $imageInfo['size'];
        }
    }

    private function saveImageInfoToCache(array $imageInfo, string $imagePath): void
    {
        $imagePath = $this->cachePrefix . $imagePath;
        $this->_cacheManager->save($this->serializer->serialize($imageInfo), $imagePath, [$this->cachePrefix]);
    }

    private function loadImageInfoFromCache(string $imagePath)
    {
        $imagePath = $this->cachePrefix . $imagePath;
        $cacheData = $this->_cacheManager->load($imagePath);
        if (!$cacheData) {
            return false;
        } else {
            return $this->serializer->unserialize($cacheData);
        }
    }

    private function clearImageInfoFromCache(): void
    {
        $this->_cacheManager->clean([$this->cachePrefix]);
    }
}
