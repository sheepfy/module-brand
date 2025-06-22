<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Ui\Component\Listing\Column;

use Blacksheep\Brand\Model\Config\Source\Status as StatusSource;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Status extends Column
{
    public const NAME = 'column.status';

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private StatusSource $statusSource,
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

        $types = $this->statusSource->toArray();
        $fieldName = 'status';
        foreach ($dataSource['data']['items'] as &$item) {
            if (isset($item[$fieldName])) {
                $item[$fieldName] = $types[$item[$fieldName]];
            }
        }

        return $dataSource;
    }
}
