<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 2;

    public function toOptionArray(): array
    {
        return [
            ['value' => self::STATUS_ACTIVE, 'label' => __('Active')->render()],
            ['value' => self::STATUS_INACTIVE, 'label' => __('Inactive')->render()],
        ];
    }

    public function toArray(): array
    {
        $options = [];
        foreach ($this->toOptionArray() as $option) {
            $options[$option['value']] = $option['label'];
        }

        return $options;
    }
}
