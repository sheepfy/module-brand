<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class BrandActions extends Column
{
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['entity_id'])) {
                continue;
            }

            $name = $this->_getData('name');
            $item[$name]['edit'] = [
                'href' => $this->context->getUrl('catalog/brand/edit', ['id' => $item['entity_id']]),
                'label' => __('Edit')->render(),
            ];

            $item[$name]['delete'] = [
                'href' => $this->context->getUrl('catalog/brand/delete', ['id' => $item['entity_id']]),
                'label' => __('Delete')->render(),
                'confirm' => [
                    'title' => __('Delete %1', $item['name'])->render(),
                    'message' => __('Are you sure you want to delete brand "%1"?', $item['name'])->render(),
                ],
            ];
        }

        return $dataSource;
    }
}
