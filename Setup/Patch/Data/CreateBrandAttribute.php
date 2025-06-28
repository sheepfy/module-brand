<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Setup\Patch\Data;

use Blacksheep\Brand\Model\Source\Brand;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class CreateBrandAttribute implements DataPatchInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory
    ) {}

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create();
        $attribute = $eavSetup->getAttribute(Product::ENTITY, 'brand');
        if ($attribute) {
            $this->moduleDataSetup->endSetup();

            return $this;
        }

        $eavSetup->addAttribute(
            Product::ENTITY,
            'brand',
            [
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Brand',
                'input' => 'select',
                'class' => '',
                'global' => ScopedAttributeInterface::SCOPE_WEBSITE,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'searchable' => true,
                'filterable' => true,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => true,
                'is_used_in_grid' => true,
                'is_filterable_in_grid' => true,
                'show_in_grid' => true,
                'system' => true,
                'unique' => false,
                'source' => Brand::class,
                'apply_to' => ''
            ]
        );

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies()
    {
        return [];
    }
}
