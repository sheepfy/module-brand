<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class IncludeImage implements OptionSourceInterface
{
    public const INCLUDE_NONE = 'none';
    public const INCLUDE_ALL = 'all';

    public function toOptionArray(): array
    {
        return [
            self::INCLUDE_NONE => __('None')->render(),
            self::INCLUDE_ALL => __('All')->render()
        ];
    }
}
