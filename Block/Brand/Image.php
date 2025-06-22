<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Block\Brand;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * @method string getImageUrl()
 * @method string getWidth()
 * @method string getHeight()
 * @method string getLabel()
 * @method float getRatio()
 * @method string getCustomAttributes()
 */
class Image extends Template
{
    public function __construct(
        Context $context,
        array $data = []
    ) {
        if (isset($data['template'])) {
            $this->setTemplate($data['template']);
            unset($data['template']);
        }

        parent::__construct($context, $data);
    }
}
