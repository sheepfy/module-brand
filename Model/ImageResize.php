<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model;

use Blacksheep\Brand\Helper\Image as ImageHelper;
use Blacksheep\Brand\Model\Brand\Image\ParamsBuilder;
use Blacksheep\Brand\Model\Brand\Media\ConfigInterface;
use Blacksheep\Brand\Model\ResourceModel\Brand\Image as BrandImage;
use Blacksheep\Brand\Model\View\Asset\ImageFactory as AssertImageFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Image;
use Magento\Framework\Image\Factory as ImageFactory;
use Magento\Framework\View\ConfigInterface as ViewConfig;
use Magento\MediaStorage\Helper\File\Storage\Database as FileStorageDatabase;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Model\Config\Customization as ThemeCustomizationConfig;
use Magento\Theme\Model\ResourceModel\Theme\Collection as ThemeCollection;
use Magento\Theme\Model\Theme;

class ImageResize
{
    private WriteInterface $mediaDirectory;

    public function __construct(
        private ConfigInterface $imageConfig,
        private BrandImage $brandImage,
        private ImageFactory $imageFactory,
        private ParamsBuilder $paramsBuilder,
        private ViewConfig $viewConfig,
        private AssertImageFactory $assertImageFactory,
        private ThemeCustomizationConfig $themeCustomizationConfig,
        private ThemeCollection $themeCollection,
        Filesystem $filesystem,
        private FileStorageDatabase $fileStorageDatabase,
        private StoreManagerInterface\Proxy $storeManager
    ) {
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    public function resizeFromImageName(string $originalImageName): void
    {
        $mediastoragefilename = $this->imageConfig->getMediaPath($originalImageName);
        $originalImagePath = $this->mediaDirectory->getAbsolutePath($mediastoragefilename);

        if ($this->fileStorageDatabase->checkDbUsage() && !$this->mediaDirectory->isFile($mediastoragefilename)) {
            $this->fileStorageDatabase->saveFileToFilesystem($mediastoragefilename);
        }

        if (!$this->mediaDirectory->isFile($originalImagePath)) {
            throw new NotFoundException(__('Cannot resize image "%1" - original image not found', $originalImagePath));
        }

        foreach ($this->getViewImages($this->getThemesInUse()) as $viewImage) {
            $this->resize($viewImage, $originalImagePath, $originalImageName);
        }
    }

    public function resizeFromThemes(?array $themes = null): \Generator
    {
        $count = $this->getCountBrandImages();
        if (!$count) {
            throw new NotFoundException(__('Cannot resize images - product images not found'));
        }

        $brandImages = $this->getBrandImages();
        $viewImages = $this->getViewImages($themes ?? $this->getThemesInUse());
        $viewImagesConfig = [];
        foreach ($viewImages as $key => $viewImage) {
            if (!isset($viewImagesConfig[$viewImage['image_type']])) {
                $viewImagesConfig[$viewImage['image_type']] = [];
            }

            $viewImagesConfig[$viewImage['image_type']][$key] = $viewImage;
        }

        foreach ($brandImages as $image) {
            $error = '';
            $originalImageName = $image['filepath'];

            $parts = explode('/', trim($originalImageName, '/'));
            $viewImages = $viewImagesConfig[$parts[0]] ?? [];

            $mediastoragefilename = $this->imageConfig->getMediaPath($originalImageName);
            $originalImagePath = $this->mediaDirectory->getAbsolutePath($mediastoragefilename);

            if ($this->fileStorageDatabase->checkDbUsage()) {
                $this->fileStorageDatabase->saveFileToFilesystem($mediastoragefilename);
            }
            if ($this->mediaDirectory->isFile($originalImagePath)) {
                try {
                    foreach ($viewImages as $viewImage) {
                        $this->resize($viewImage, $originalImagePath, $originalImageName);
                    }
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }
            } else {
                $error = __('Cannot resize image "%1" - original image not found', $originalImagePath);
            }

            yield ['filename' => $originalImageName, 'error' => (string) $error] => $count;
        }
    }

    public function getCountBrandImages(): int
    {
        return $this->brandImage->getCountAllBrandImages();
    }

    public function getBrandImages(): \Generator
    {
        return $this->brandImage->getAllBrandImages();
    }

    private function getThemesInUse(): array
    {
        $themesInUse = [];
        $registeredThemes = $this->themeCollection->loadRegisteredThemes();
        $storesByThemes = $this->themeCustomizationConfig->getStoresByThemes();
        $keyType = is_integer(key($storesByThemes)) ? 'getId' : 'getCode';
        foreach ($registeredThemes as $registeredTheme) {
            if (array_key_exists($registeredTheme->$keyType(), $storesByThemes)) {
                $themesInUse[] = $registeredTheme;
            }
        }

        return $themesInUse;
    }

    private function getViewImages(array $themes): array
    {
        $viewImages = [];
        $stores = $this->storeManager->getStores(true);
        /** @var Theme $theme */
        foreach ($themes as $theme) {
            $config = $this->viewConfig->getViewConfig([
                'area' => Area::AREA_FRONTEND,
                'themeModel' => $theme,
            ]);
            $images = $config->getMediaEntities('Blacksheep_Brand', ImageHelper::MEDIA_TYPE_CONFIG_NODE);
            foreach ($images as $imageId => $imageData) {
                foreach ($stores as $store) {
                    $data = $this->paramsBuilder->build($imageData, (int) $store->getId());
                    $uniqIndex = $this->getUniqueImageIndex($data);
                    $data['id'] = $imageId;
                    $viewImages[$uniqIndex] = $data;
                }
            }
        }

        return $viewImages;
    }

    private function getUniqueImageIndex(array $imageData): string
    {
        ksort($imageData);
        unset($imageData['type']);
        // phpcs:disable Magento2.Security.InsecureFunction
        return md5(json_encode($imageData));
    }

    private function makeImage(string $originalImagePath, array $imageParams): Image
    {
        $image = $this->imageFactory->create($originalImagePath);
        $image->keepAspectRatio($imageParams['keep_aspect_ratio']);
        $image->keepFrame($imageParams['keep_frame']);
        $image->keepTransparency($imageParams['keep_transparency']);
        $image->constrainOnly($imageParams['constrain_only']);
        $image->backgroundColor($imageParams['background']);
        $image->quality($imageParams['quality']);

        return $image;
    }

    private function resize(array $imageParams, string $originalImagePath, string $originalImageName): void
    {
        unset($imageParams['id']);
        $imageAsset = $this->assertImageFactory->create([
            'miscParams' => $imageParams,
            'filePath' => $originalImageName,
        ]);
        $imageAssetPath = $imageAsset->getPath();
        $usingDbAsStorage = $this->fileStorageDatabase->checkDbUsage();
        $mediaStorageFilename = $this->mediaDirectory->getRelativePath($imageAssetPath);

        $alreadyResized = $usingDbAsStorage ?
            $this->fileStorageDatabase->fileExists($mediaStorageFilename) :
            $this->mediaDirectory->isFile($imageAssetPath);

        if (!$alreadyResized) {
            $this->generateResizedImage(
                $imageParams,
                $originalImagePath,
                $imageAssetPath,
                $usingDbAsStorage,
                $mediaStorageFilename
            );
        }
    }

    private function generateResizedImage(
        array $imageParams,
        string $originalImagePath,
        string $imageAssetPath,
        bool $usingDbAsStorage,
        string $mediaStorageFilename
    ): void {
        $image = $this->makeImage($originalImagePath, $imageParams);

        if ($imageParams['image_width'] !== null && $imageParams['image_height'] !== null) {
            $image->resize($imageParams['image_width'], $imageParams['image_height']);
        }

        if (isset($imageParams['watermark_file'])) {
            if ($imageParams['watermark_height'] !== null) {
                $image->setWatermarkHeight($imageParams['watermark_height']);
            }

            if ($imageParams['watermark_width'] !== null) {
                $image->setWatermarkWidth($imageParams['watermark_width']);
            }

            if ($imageParams['watermark_position'] !== null) {
                $image->setWatermarkPosition($imageParams['watermark_position']);
            }

            if ($imageParams['watermark_image_opacity'] !== null) {
                $image->setWatermarkImageOpacity($imageParams['watermark_image_opacity']);
            }

            $image->watermark($this->getWatermarkFilePath($imageParams['watermark_file']));
        }

        $image->save($imageAssetPath);

        if ($usingDbAsStorage) {
            $this->fileStorageDatabase->saveFile($mediaStorageFilename);
        }
    }

    private function getWatermarkFilePath($file)
    {
        $path = $this->imageConfig->getMediaPath('/watermark/' . $file);

        return $this->mediaDirectory->getAbsolutePath($path);
    }
}
