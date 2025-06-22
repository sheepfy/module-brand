<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\View\Asset;

use Blacksheep\Brand\Helper\Image as ImageHelper;
use Blacksheep\Brand\Model\Brand\Image\ConvertImageMiscParamsToReadableFormat;
use Blacksheep\Brand\Model\Brand\Media\ConfigInterface;
use Magento\Catalog\Model\Config\CatalogMediaConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\ContextInterface;
use Magento\Framework\View\Asset\LocalInterface;
use Magento\Store\Model\StoreManagerInterface;

class Image implements LocalInterface
{
    /**
     * Current hashing algorithm
     */
    private const HASH_ALGORITHM = 'md5';

    /**
     * Image type of image (thumbnail,small_image,image,swatch_image,swatch_thumb)
     *
     * @var string
     */
    private $sourceContentType;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var string
     */
    private $contentType = 'image';

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * Misc image params depend on size, transparency, quality, watermark etc.
     *
     * @var array
     */
    private $miscParams;

    /**
     * @var ConfigInterface
     */
    private $mediaConfig;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var string
     */
    private $mediaFormatUrl;

    /**
     * @var ConvertImageMiscParamsToReadableFormat
     */
    private $convertImageMiscParamsToReadableFormat;

    public function __construct(
        ConfigInterface $mediaConfig,
        ContextInterface $context,
        EncryptorInterface $encryptor,
        $filePath,
        array $miscParams,
        ?ImageHelper $imageHelper = null,
        ?CatalogMediaConfig $catalogMediaConfig = null,
        ?StoreManagerInterface $storeManager = null,
        ?ConvertImageMiscParamsToReadableFormat $convertImageMiscParamsToReadableFormat = null
    ) {
        if (isset($miscParams['image_type'])) {
            $this->sourceContentType = $miscParams['image_type'];
            unset($miscParams['image_type']);
        } else {
            $this->sourceContentType = $this->contentType;
        }
        $this->mediaConfig = $mediaConfig;
        $this->context = $context;
        $this->filePath = $filePath;
        $this->miscParams = $miscParams;
        $this->encryptor = $encryptor;
        $this->imageHelper = $imageHelper ?: ObjectManager::getInstance()->get(ImageHelper::class);
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);

        $catalogMediaConfig =  $catalogMediaConfig ?: ObjectManager::getInstance()->get(CatalogMediaConfig::class);
        $this->mediaFormatUrl = $catalogMediaConfig->getMediaUrlFormat();
        $this->convertImageMiscParamsToReadableFormat = $convertImageMiscParamsToReadableFormat ?:
            ObjectManager::getInstance()->get(ConvertImageMiscParamsToReadableFormat::class);
    }

    public function getUrl()
    {
        switch ($this->mediaFormatUrl) {
            case CatalogMediaConfig::IMAGE_OPTIMIZATION_PARAMETERS:
                return $this->getUrlWithTransformationParameters();
            case CatalogMediaConfig::HASH:
                return $this->context->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getImageInfo();
            default:
                throw new LocalizedException(__(
                    "The specified Catalog media URL format '$this->mediaFormatUrl' is not supported."
                ));
        }
    }

    private function getUrlWithTransformationParameters()
    {
        return $this->getOriginalImageUrl() . '?' . http_build_query($this->getImageTransformationParameters());
    }

    public function getImageTransformationParameters()
    {
        return [
            'width' => $this->miscParams['image_width'],
            'height' => $this->miscParams['image_height'],
            'store' => $this->storeManager->getStore()->getCode(),
            'image-type' => $this->sourceContentType
        ];
    }

    private function getOriginalImageUrl()
    {
        $originalImageFile = $this->getSourceFile();
        if (!$originalImageFile) {
            return $this->imageHelper->getDefaultPlaceholderUrl();
        } else {
            return $this->context->getBaseUrl() . $this->getFilePath();
        }
    }

    public function getContentType()
    {
        return $this->contentType;
    }

    public function getPath()
    {
        return $this->context->getPath() . DIRECTORY_SEPARATOR . $this->getImageInfo();
    }

    public function getSourceFile()
    {
        $path = $this->getFilePath() ? ltrim($this->getFilePath(), DIRECTORY_SEPARATOR) : '';

        return $this->mediaConfig->getBaseMediaPath() . DIRECTORY_SEPARATOR . $path;
    }

    public function getSourceContentType()
    {
        return $this->sourceContentType;
    }

    public function getContent()
    {
        return null;
    }

    public function getFilePath()
    {
        return $this->filePath;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function getModule()
    {
        return 'cache';
    }

    private function getImageInfo()
    {
        $data = implode('_', $this->convertToReadableFormat($this->miscParams));

        $pathTemplate = $this->getModule()
            . DIRECTORY_SEPARATOR . "%s" . DIRECTORY_SEPARATOR
            . $this->getFilePath();

        /**
         * New paths are generated without dependency on
         * an encryption key.
         */
        return preg_replace(
            '|\Q' . DIRECTORY_SEPARATOR . '\E+|',
            DIRECTORY_SEPARATOR,
            sprintf($pathTemplate, hash(self::HASH_ALGORITHM, $data))
        );
    }

    private function convertToReadableFormat(array $miscParams)
    {
        return $this->convertImageMiscParamsToReadableFormat->convertImageMiscParamsToReadableFormat($miscParams);
    }
}
