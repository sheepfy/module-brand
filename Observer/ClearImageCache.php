<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Observer;

use Blacksheep\Brand\Model\Brand\Image;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ClearImageCache implements ObserverInterface
{
    public function __construct(
        private Image $image
    ) {}

    public function execute(Observer $observer)
    {
        $this->image->clearCache();
    }
}
