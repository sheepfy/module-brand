<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Ui\Component\Listing\Column;

use Blacksheep\Brand\Helper\Image as ImageHelper;
use Blacksheep\Brand\Model\BrandFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Thumbnail extends Column
{
    public const ALT_FIELD = 'name';

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private ImageHelper $imageHelper,
        private BrandFactory $brandFactory,
        private UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $fieldName = $this->getData('name');
        foreach ($dataSource['data']['items'] as & $item) {
            /** @var \Blacksheep\Brand\Model\Brand $brand */
            $brand = $this->brandFactory->create(['data' => $item]);
            $imageHelper = $this->imageHelper->init($brand, 'brand_listing_thumbnail');
            $item[$fieldName . '_src'] = $imageHelper->getUrl();
            $item[$fieldName . '_alt'] = $this->getAlt($item) ?: $imageHelper->getLabel();
            $item[$fieldName . '_link'] = $this->urlBuilder->getUrl('*/*/edit', [
                'id' => $brand->getEntityId(),
                'store' => $this->context->getRequestParam('store')
            ]);
            $origImageHelper = $this->imageHelper->init($brand, 'brand_listing_thumbnail_preview');
            $item[$fieldName . '_orig_src'] = $origImageHelper->getUrl();
        }

        return $dataSource;
    }

    private function getAlt(array $row): string
    {
        $altField = $this->getData('config/altField') ?: self::ALT_FIELD;
        // phpcs:disable Magento2.Functions.DiscouragedFunction
        return html_entity_decode($row[$altField], ENT_QUOTES, "UTF-8") ?? '';
    }
}
