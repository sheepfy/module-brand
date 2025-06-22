<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\System\Config\Backend\Brand\Url\Rewrite;

use Blacksheep\Brand\Model\BrandUrlPathGenerator;
use Blacksheep\Brand\Model\BrandUrlRewriteGenerator;

class Suffix extends \Blacksheep\Brand\Model\System\Config\Backend\Brand\Url\Rewrite
{
    public function beforeSave()
    {
        $this->urlRewriteHelper->validateSuffix($this->getValue());

        return $this;
    }

    public function afterSave()
    {
        if (!$this->isValueChanged()) {
            return parent::afterSave();
        }

        $this->updateForUrlRewrites([
            BrandUrlPathGenerator::XML_PATH_BRAND_URL_SUFFIX => BrandUrlRewriteGenerator::ENTITY_TYPE,
        ]);

        return parent::afterSave();
    }

    protected function getPattern(): string
    {
        return '~' . preg_quote($this->getOldValue()) . '$~';
    }
}
