<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Setup\Patch\Data;

use Blacksheep\Brand\Model\Source\Brand;
use Magento\Catalog\Api\Data\CategoryAttributeInterface;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class CreateBrandCategoryAttribute implements DataPatchInterface, PatchRevertableInterface
{
    private const CATEGORY_ATTRIBUTE_CODE = 'brand';

    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private CategorySetupFactory $categorySetupFactory
    ) {}

    /**
     * @inheritDoc
     */
    public function apply(): static
    {
        $categorySetup = $this->getCategorySetup();
        $categorySetup->addAttribute(
            CategoryAttributeInterface::ENTITY_TYPE_CODE,
            self::CATEGORY_ATTRIBUTE_CODE,
            [
                'type' => 'int',
                'frontend' => '',
                'backend' => '',
                'label' => 'Brand',
                'input' => 'select',
                'global' => ScopedAttributeInterface::SCOPE_WEBSITE,
                'unique' => false,
                'class' => '',
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
                'source' => Brand::class,
                'apply_to' => ''
            ]
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function revert(): void
    {
        /** @var CategorySetup $setup */
        $categorySetup = $this->getCategorySetup();
        $categorySetup->removeAttribute(
            CategoryAttributeInterface::ENTITY_TYPE_CODE,
            'brand'
        );
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get / create a category setup model
     *
     * @return CategorySetup
     */
    private function getCategorySetup(): CategorySetup
    {
        return $this->categorySetupFactory->create([
            'setup' => $this->moduleDataSetup,
        ]);
    }
}
