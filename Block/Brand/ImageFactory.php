<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Block\Brand;

use Blacksheep\Brand\Api\Data\BrandInterface;
use Blacksheep\Brand\Block\Brand\Image as ImageBlock;
use Blacksheep\Brand\Model\Brand\Image\ParamsBuilder;
use Blacksheep\Brand\Model\View\Asset\ImageFactory as AssetImageFactory;
use Blacksheep\Brand\Model\View\Asset\PlaceholderFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\ConfigInterface;

class ImageFactory
{
    public const MEDIA_TYPE_CONFIG_NODE = 'images';

    public function __construct(
        private ObjectManagerInterface $objectManager,
        private ConfigInterface $presentationConfig,
        private AssetImageFactory $viewAssetImageFactory,
        private PlaceholderFactory $viewAssetPlaceholderFactory,
        private ParamsBuilder $imageParamsBuilder
    ) {}

    private function filterCustomAttributes(array $attributes): array
    {
        if (isset($attributes['class'])) {
            unset($attributes['class']);
        }

        return $attributes;
    }

    private function getClass(array $attributes): string
    {
        return $attributes['class'] ?? 'brand-image-logo';
    }

    private function getRatio(int $width, int $height): float
    {
        if ($width && $height) {
            return $height / $width;
        }

        return 1.0;
    }

    private function getLabel(BrandInterface $brand, string $imageType): string
    {
        if (!$label = $brand->getData($imageType . '_' . 'label')) {
            $label = $brand->getName();
        }

        return (string) $label;
    }

    public function create(BrandInterface $brand, string $imageId, array $attributes = []): ImageBlock
    {
        $viewImageConfig = $this->presentationConfig->getViewConfig()->getMediaAttributes(
            'Blacksheep_Brand',
            self::MEDIA_TYPE_CONFIG_NODE,
            $imageId
        );

        $imageMiscParams = $this->imageParamsBuilder->build($viewImageConfig);
        $originalFilePath = $brand->getData($imageMiscParams['image_type']);

        if ($originalFilePath === null || $originalFilePath === 'no_selection') {
            $imageAsset = $this->viewAssetPlaceholderFactory->create([
                'type' => $imageMiscParams['image_type']
            ]);
        } else {
            $imageAsset = $this->viewAssetImageFactory->create([
                'miscParams' => $imageMiscParams,
                'filePath' => $originalFilePath,
            ]);
        }

        return $this->objectManager->create(ImageBlock::class, [
            'data' => [
                'template' => 'Blacksheep_Brand::brand/image_with_borders.phtml',
                'image_url' => $imageAsset->getUrl(),
                'width' => $imageMiscParams['image_width'],
                'height' => $imageMiscParams['image_height'],
                'label' => $this->getLabel($brand, $imageMiscParams['image_type']),
                'ratio' => $this->getRatio($imageMiscParams['image_width'] ?? 0, $imageMiscParams['image_height'] ?? 0),
                'custom_attributes' => $this->filterCustomAttributes($attributes),
                'class' => $this->getClass($attributes),
                'brand_id' => $brand->getEntityId()
            ],
        ]);
    }
}
