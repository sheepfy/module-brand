<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\System\Config\Backend\Brand\Url\Rewrite;

use Blacksheep\Brand\Model\BrandUrlPathGenerator;
use Blacksheep\Brand\Model\BrandUrlRewriteGenerator;

class Prefix extends \Blacksheep\Brand\Model\System\Config\Backend\Brand\Url\Rewrite
{
    public function beforeSave()
    {
        $this->urlRewriteHelper->validateRequestPath($this->getValue());

        return $this;
    }

    public function afterSave()
    {
        if (!$this->isValueChanged()) {
            return parent::afterSave();
        }

        $this->updateForUrlRewrites([
            BrandUrlPathGenerator::XML_PATH_BRAND_URL_PREFIX => BrandUrlRewriteGenerator::ENTITY_TYPE,
        ]);

        return parent::afterSave();
    }

    protected function getUrlPath(string $value, string $path): string
    {
        if ($value && !preg_match('~^' . preg_quote($this->getOldValue()) . '/~', $path)) {
            return $value . $path;
        }

        return preg_replace($this->getPattern(), $value, $path);
    }

    protected function getPattern(): string
    {
        return '~^[^/]+/~';
    }
}
