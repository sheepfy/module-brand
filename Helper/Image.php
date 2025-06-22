<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Helper;

use Blacksheep\Brand\Api\Data\BrandInterface;
use Blacksheep\Brand\Model\Brand\Image as BrandImageModel;
use Blacksheep\Brand\Model\Brand\ImageFactory as BrandImageFactory;
use Magento\Catalog\Model\Config\CatalogMediaConfig;
use Magento\Catalog\Model\View\Asset\PlaceholderFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Config\View as ConfigView;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\ConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;

class Image extends AbstractHelper implements ArgumentInterface
{
    public const MEDIA_TYPE_CONFIG_NODE = 'images';

    private ?BrandImageModel $model = null;

    private bool $scheduleResize = true;

    private bool $scheduleRotate = false;

    private ?int $angle = null;

    private ?string $watermark = null;

    private ?string $watermarkPosition = null;

    private ?string $watermarkSize = null;

    private ?int $watermarkImageOpacity = null;

    private ?BrandInterface $brand = null;

    private ?string $imageFile = null;

    private array $attributes = [];

    private ?string $placeholder = null;

    private ?ConfigView $configView = null;

    public function __construct(
        Context $context,
        private BrandImageFactory $brandImageFactory,
        private ConfigInterface $viewConfig,
        private PlaceholderFactory $viewAssetPlaceholderFactory,
        private CatalogMediaConfig $mediaConfig
    ) {
        parent::__construct($context);
    }

    private function reset(): void
    {
        $this->model = null;
        $this->scheduleRotate = false;
        $this->angle = null;
        $this->watermark = null;
        $this->watermarkPosition = null;
        $this->watermarkSize = null;
        $this->watermarkImageOpacity = null;
        $this->brand = null;
        $this->imageFile = null;
        $this->attributes = [];
    }

    public function init(BrandInterface $brand, string $imageId, array $attributes = []): self
    {
        $this->reset();

        $this->attributes = array_merge(
            $this->getConfigView()->getMediaAttributes('Blacksheep_Brand', self::MEDIA_TYPE_CONFIG_NODE, $imageId),
            $attributes
        );

        $this->setBrand($brand);
        $this->setImageProperties();
        $this->setWatermarkProperties();

        return $this;
    }

    private function setImageProperties(): void
    {
        $this->getModel()->setDestinationSubdir($this->getType());
        $this->getModel()->setWidth($this->getWidth());
        $this->getModel()->setHeight($this->getHeight());

        // Set 'keep frame' flag
        $frame = $this->getFrame();
        $this->getModel()->setKeepFrame($frame);

        // Set 'constrain only' flag
        $constrain = $this->getAttribute('constrain');
        if (null !== $constrain) {
            $this->getModel()->setConstrainOnly($constrain);
        }

        // Set 'keep aspect ratio' flag
        $aspectRatio = $this->getAttribute('aspect_ratio');
        if (null !== $aspectRatio) {
            $this->getModel()->setKeepAspectRatio($aspectRatio);
        }

        // Set 'transparency' flag
        $transparency = $this->getAttribute('transparency');
        if (null !== $transparency) {
            $this->getModel()->setKeepTransparency($transparency);
        }

        // Set background color
        $background = $this->getAttribute('background');
        if (null !== $background) {
            $this->getModel()->setBackgroundColor($background);
        }
    }

    private function setWatermarkProperties(): void
    {
        $this->setWatermark($this->scopeConfig->getValue(
            "design/watermark_brand/{$this->getType()}_image",
            ScopeInterface::SCOPE_STORE
        ));
        $this->setWatermarkImageOpacity($this->scopeConfig->getValue(
            "design/watermark_brand/{$this->getType()}_imageOpacity",
            ScopeInterface::SCOPE_STORE
        ));
        $this->setWatermarkPosition($this->scopeConfig->getValue(
            "design/watermark_brand/{$this->getType()}_position",
            ScopeInterface::SCOPE_STORE
        ));
        $this->setWatermarkSize($this->scopeConfig->getValue(
            "design/watermark_brand/{$this->getType()}_size",
            ScopeInterface::SCOPE_STORE
        ));
    }

    public function resize($width, $height = null): void
    {
        $this->getModel()->setWidth($width)->setHeight($height);
        $this->scheduleResize = true;
    }

    public function keepAspectRatio($flag): void
    {
        $this->getModel()->setKeepAspectRatio($flag);
    }

    public function keepFrame($flag): void
    {
        $this->getModel()->setKeepFrame($flag);
    }

    public function keepTransparency($flag): void
    {
        $this->getModel()->setKeepTransparency($flag);
    }

    public function constrainOnly($flag): void
    {
        $this->getModel()->setConstrainOnly($flag);
    }

    public function backgroundColor($colorRGB): void
    {
        // assume that 3 params were given instead of array
        if (!is_array($colorRGB)) {
            //phpcs:disable
            $colorRGB = func_get_args();
            //phpcs:enabled
        }
        $this->getModel()->setBackgroundColor($colorRGB);
    }

    public function rotate($angle): void
    {
        $this->setAngle($angle);
        $this->getModel()->setAngle($angle);
        $this->scheduleRotate = true;
    }

    public function watermark($fileName, $position, $size = null, $imageOpacity = null): void
    {
        $this->setWatermark($fileName);
        $this->setWatermarkPosition($position);
        $this->setWatermarkSize($size);
        $this->setWatermarkImageOpacity($imageOpacity);
    }

    public function placeholder($fileName): void
    {
        $this->placeholder = $fileName;
    }

    public function getPlaceholder(?string $placeholder = null): string
    {
        $path = 'Blacksheep_Brand::images/brand/placeholder/%s.jpg';
        if ($placeholder) {
            return sprintf($path, $placeholder);
        }

        return $this->placeholder ?: sprintf($path, $this->getModel()->getDestinationSubdir());
    }

    private function applyScheduledActions(): void
    {
        $this->initBaseFile();
        if (!$this->isScheduledActionsAllowed()) {
            return;
        }

        $model = $this->getModel();
        if ($this->scheduleRotate) {
            $model->rotate($this->getAngle());
        }
        if ($this->scheduleResize) {
            $model->resize();
        }
        if ($this->getWatermark()) {
            $model->setWatermark($this->getWatermark());
        }
        $model->saveFile();
    }

    private function initBaseFile(): void
    {
        $model = $this->getModel();
        $baseFile = $model->getBaseFile();
        if ($baseFile) {
            return;
        }

        if ($this->getImageFile()) {
            $model->setBaseFile($this->getImageFile());
        } else {
            $model->setBaseFile(
                $this->getBrand() ? $this->getBrand()->getData($model->getDestinationSubdir()) : ''
            );
        }
    }

    private function isScheduledActionsAllowed(): bool
    {
        $model = $this->getModel();
        if ($model->isBaseFilePlaceholder() || $model->isCached()) {
            return false;
        }

        return true;
    }

    public function getUrl(): string
    {
        try {
            switch ($this->mediaConfig->getMediaUrlFormat()) {
                case CatalogMediaConfig::IMAGE_OPTIMIZATION_PARAMETERS:
                    $this->initBaseFile();
                    break;
                case CatalogMediaConfig::HASH:
                    $this->applyScheduledActions();
                    break;
                default:
                    throw new LocalizedException(__("The specified Catalog media URL format is not supported."));
            }
            return $this->getModel()->getUrl();
        } catch (\Exception $e) {
            return $this->getDefaultPlaceholderUrl();
        }
    }

    public function save(): void
    {
        $this->applyScheduledActions();
    }

    public function getResizedImageInfo(): array
    {
        $this->applyScheduledActions();

        return $this->getModel()->getResizedImageInfo();
    }

    public function getDefaultPlaceholderUrl(?string $placeholder = null): string
    {
        try {
            $imageAsset = $this->viewAssetPlaceholderFactory->create([
                'type' => $placeholder ?: $this->getModel()->getDestinationSubdir(),
            ]);
            $url = $imageAsset->getUrl();
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $url = $this->_urlBuilder->getUrl('', ['_direct' => 'core/index/notFound']);
        }

        return $url;
    }

    private function getModel(): BrandImageModel
    {
        if (!$this->model) {
            $this->model = $this->brandImageFactory->create();
        }

        return $this->model;
    }

    private function setAngle(?int $angle): void
    {
        $this->angle = $angle;
    }

    private function getAngle(): ?int
    {
        return $this->angle;
    }

    private function setWatermark(?string $watermark): void
    {
        $this->watermark = $watermark;
        $this->getModel()->setWatermarkFile($watermark);
    }

    private function getWatermark(): ?string
    {
        return $this->watermark;
    }

    private function setWatermarkPosition(?string $position): void
    {
        $this->watermarkPosition = $position;
        $this->getModel()->setWatermarkPosition($position);
    }

    private function getWatermarkPosition(): ?string
    {
        return $this->watermarkPosition;
    }

    public function setWatermarkSize(?string $size): void
    {
        $this->watermarkSize = $size;
        $this->getModel()->setWatermarkSize($this->parseSize($size));
    }

    private function getWatermarkSize(): ?string
    {
        return $this->watermarkSize;
    }

    public function setWatermarkImageOpacity(?int $imageOpacity): void
    {
        $this->watermarkImageOpacity = $imageOpacity;
        $this->getModel()->setWatermarkImageOpacity($imageOpacity);
    }

    private function getWatermarkImageOpacity(): ?int
    {
        if ($this->watermarkImageOpacity) {
            return $this->watermarkImageOpacity;
        }

        return $this->getModel()->getWatermarkImageOpacity();
    }

    private function setBrand(BrandInterface $brand): void
    {
        $this->brand = $brand;
    }

    private function getBrand(): BrandInterface
    {
        return $this->brand;
    }

    public function setImageFile(string $file): void
    {
        $this->imageFile = $file;
    }

    private function getImageFile(): ?string
    {
        return $this->imageFile;
    }

    private function parseSize(?string $string): array
    {
        $size = $string !== null ? explode('x', strtolower($string)) : [];
        if (count($size) === 2) {
            return ['width' => $size[0] > 0 ? $size[0] : null, 'height' => $size[1] > 0 ? $size[1] : null];
        }

        return [];
    }

    public function getOriginalWidth()
    {
        return $this->getModel()->getImageProcessor()->getOriginalWidth();
    }

    public function getOriginalHeight()
    {
        return $this->getModel()->getImageProcessor()->getOriginalHeight();
    }

    public function getOriginalSizeArray(): array
    {
        return [$this->getOriginalWidth(), $this->getOriginalHeight()];
    }

    private function getConfigView(): ConfigView
    {
        if (!$this->configView) {
            $this->configView = $this->viewConfig->getViewConfig();
        }

        return $this->configView;
    }

    public function getType(): string
    {
        return $this->getAttribute('type');
    }

    public function getWidth(): ?int
    {
        return $this->getAttribute('width');
    }

    public function getHeight(): ?int
    {
        return $this->getAttribute('height') ?: $this->getAttribute('width');
    }

    public function getFrame(): bool
    {
        $frame = $this->getAttribute('frame');
        if ($frame === null) {
            $frame = $this->getConfigView()->getVarValue('Blacksheep_Brand', 'brand_image_white_borders');
        }

        return (bool)$frame;
    }

    private function getAttribute(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function getLabel(): string
    {
        $label = $this->brand->getData($this->getType() . '_' . 'label');
        if (!$label) {
            $label = $this->brand->getName();
        }

        return $label;
    }
}
