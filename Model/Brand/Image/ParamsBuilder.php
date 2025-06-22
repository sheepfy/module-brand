<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\Brand\Image;

use Blacksheep\Brand\Model\Brand\Image;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\ConfigInterface;
use Magento\Framework\View\Design\Theme\FlyweightFactory;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\ScopeInterface;

class ParamsBuilder
{
    private array $defaultBackground = [255, 255, 255];
    private ?int $defaultAngle = null;
    private bool $defaultKeepAspectRatio = true;
    private bool $defaultKeepTransparency = true;
    private bool $defaultConstrainOnly = true;
    private ?ThemeInterface $currentTheme = null;
    private array $themesList = [];

    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private ConfigInterface $viewConfig,
        private DesignInterface $design,
        private FlyweightFactory $themeFactory
    ) {}

    public function build(array $imageArguments, ?int $scopeId = null): array
    {
        $this->determineCurrentTheme($scopeId);

        $miscParams = [
            'image_type' => $imageArguments['type'] ?? null,
            'image_height' => $imageArguments['height'] ?? null,
            'image_width' => $imageArguments['width'] ?? null,
        ];

        $overwritten = $this->overwriteDefaultValues($imageArguments);
        $watermark = isset($miscParams['image_type']) ? $this->getWatermark($miscParams['image_type'], $scopeId) : [];

        return array_merge($miscParams, $overwritten, $watermark);
    }

    private function determineCurrentTheme(?int $scopeId = null): void
    {
        if (is_numeric($scopeId) || !$this->currentTheme) {
            $themeId = $this->design->getConfigurationDesignTheme(Area::AREA_FRONTEND, ['store' => $scopeId]);
            if (isset($this->themesList[$themeId])) {
                $this->currentTheme = $this->themesList[$themeId];
            } else {
                $this->currentTheme = $this->themeFactory->create($themeId);
                $this->themesList[$themeId] = $this->currentTheme;
            }
        }
    }

    private function overwriteDefaultValues(array $imageArguments): array
    {
        $frame = $imageArguments['frame'] ?? $this->hasDefaultFrame();
        $constrain = $imageArguments['constrain'] ?? $this->defaultConstrainOnly;
        $aspectRatio = $imageArguments['aspect_ratio'] ?? $this->defaultKeepAspectRatio;
        $transparency = $imageArguments['transparency'] ?? $this->defaultKeepTransparency;
        $background = $imageArguments['background'] ?? $this->defaultBackground;
        $angle = $imageArguments['angle'] ?? $this->defaultAngle;
        $quality = (int) $this->scopeConfig->getValue(Image::XML_PATH_JPEG_QUALITY);

        return [
            'background' => (array) $background,
            'angle' => $angle,
            'quality' => $quality,
            'keep_aspect_ratio' => (bool) $aspectRatio,
            'keep_frame' => (bool) $frame,
            'keep_transparency' => (bool) $transparency,
            'constrain_only' => (bool) $constrain,
        ];
    }

    private function getWatermark(string $type, ?int $scopeId = null): array
    {
        $file = $this->scopeConfig->getValue(
            "design/watermark_brand/{$type}_image",
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );

        if ($file) {
            $size = explode(
                'x',
                (string) $this->scopeConfig->getValue(
                    "design/watermark_brand/{$type}_size",
                    ScopeInterface::SCOPE_STORE,
                    $scopeId
                )
            );
            $opacity = $this->scopeConfig->getValue(
                "design/watermark_brand/{$type}_imageOpacity",
                ScopeInterface::SCOPE_STORE,
                $scopeId
            );
            $position = $this->scopeConfig->getValue(
                "design/watermark_brand/{$type}_position",
                ScopeInterface::SCOPE_STORE,
                $scopeId
            );
            $width = $size['0'] ?? null;
            $height = $size['1'] ?? null;

            return [
                'watermark_file' => $file,
                'watermark_image_opacity' => $opacity,
                'watermark_position' => $position,
                'watermark_width' => $width,
                'watermark_height' => $height
            ];
        }

        return [];
    }

    private function hasDefaultFrame(): bool
    {
        return (bool) $this->viewConfig->getViewConfig([
            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
            'themeModel' => $this->currentTheme
        ])->getVarValue('Blacksheep_Brand', 'brand_image_white_borders');
    }
}
