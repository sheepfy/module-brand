<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\Brand\Image;

use Blacksheep\Brand\Helper\Image;
use Blacksheep\Brand\Model\View\Asset\ImageFactory;
use Blacksheep\Brand\Model\View\Asset\PlaceholderFactory;
use Magento\Framework\View\ConfigInterface;

class UrlBuilder
{
    public function __construct(
        private ConfigInterface $presentationConfig,
        private ParamsBuilder $imageParamsBuilder,
        private ImageFactory $viewAssetImageFactory,
        private PlaceholderFactory $placeholderFactory
    ) {}

    public function getUrl(string $baseFilePath, string $imageDisplayArea): string
    {
        $imageArguments = $this->presentationConfig->getViewConfig()->getMediaAttributes(
            'Blacksheep_Brand',
            Image::MEDIA_TYPE_CONFIG_NODE,
            $imageDisplayArea
        );

        $imageMiscParams = $this->imageParamsBuilder->build($imageArguments);

        if (!$baseFilePath || $baseFilePath === 'no_selection') {
            $asset = $this->placeholderFactory->create([
                'type' => $imageMiscParams['image_type']
            ]);
        } else {
            $asset = $this->viewAssetImageFactory->create([
                'miscParams' => $imageMiscParams,
                'filePath' => $baseFilePath,
            ]);
        }

        return $asset->getUrl();
    }
}
