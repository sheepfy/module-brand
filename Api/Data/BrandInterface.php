<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface BrandInterface extends ExtensibleDataInterface
{
    public const ENTITY_ID = 'entity_id';
    public const NAME = 'name';
    public const PAGE_TITLE = 'page_title';
    public const DESCRIPTION = 'description';
    public const URL_KEY = 'url_key';
    public const IMAGE = 'image';
    public const LOGO = 'logo';
    public const STATUS = 'status';
    public const PRIORITY = 'priority';
    public const SHOW_IN_CAROUSEL = 'show_in_carousel';
    public const META_TITLE = 'meta_title';
    public const META_KEYWORDS = 'meta_keywords';
    public const META_DESCRIPTION = 'meta_description';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * @param int|null $entityId
     * @return self
     */
    public function setEntityId($entityId);

    /**
     * @return int|null
     */
    public function getEntityId();

    /**
     * @param string $name
     * @return self
     */
    public function setName(string $name): self;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string|null $pageTitle
     * @return self
     */
    public function setPageTitle(?string $pageTitle): self;

    /**
     * @return string|null
     */
    public function getPageTitle(): ?string;

    /**
     * @param string|null $description
     * @return self
     */
    public function setDescription(?string $description): self;

    /**
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * @param string $urlKey
     * @return self
     */
    public function setUrlKey(string $urlKey): self;

    /**
     * @return string
     */
    public function getUrlKey(): string;

    /**
     * @param int $status
     * @return self
     */
    public function setStatus(int $status): self;

    /**
     * @return int
     */
    public function getStatus(): int;

    /**
     * @param string|null $image
     * @return self
     */
    public function setImage(?string $image = null): self;

    /**
     * @return string|null
     */
    public function getImage(): ?string;

    /**
     * @param string|null $logo
     * @return self
     */
    public function setLogo(?string $logo = null): self;

    /**
     * @return string|null
     */
    public function getLogo(): ?string;

    /**
     * @param int $priority
     * @return self
     */
    public function setPriority(int $priority): self;

    /**
     * @return int
     */
    public function getPriority(): int;

    /**
     * @param bool $showInCarousel
     * @return self
     */
    public function setShowInCarousel(bool $showInCarousel): self;

    /**
     * @return bool
     */
    public function getShowInCarousel(): bool;

    /**
     * @param string|null $metaTitle
     * @return self
     */
    public function setMetaTitle(?string $metaTitle): self;

    /**
     * @return string|null
     */
    public function getMetaTitle(): ?string;

    /**
     * @param string|null $metaDescription
     * @return self
     */
    public function setMetaDescription(?string $metaDescription): self;

    /**
     * @return string|null
     */
    public function getMetaDescription(): ?string;

    /**
     * @param string|null $metaKeywords
     * @return self
     */
    public function setMetaKeywords(?string $metaKeywords): self;

    /**
     * @return string|null
     */
    public function getMetaKeywords(): ?string;

    /**
     * @param string $createdAt
     * @return self
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * @param string $updatedAt
     * @return self
     */
    public function setUpdatedAt(string $updatedAt): self;

    /**
     * @return string
     */
    public function getUpdatedAt(): string;

    /**
     * @param \Blacksheep\Brand\Api\Data\BrandExtensionInterface $extensionAttributes
     * @return \Blacksheep\Brand\Api\Data\BrandInterface
     */
    public function setExtensionAttributes(
        \Blacksheep\Brand\Api\Data\BrandExtensionInterface $extensionAttributes
    ): self;

    /**
     * @param \Blacksheep\Brand\Api\Data\BrandExtensionInterface $extensionAttributes
     * @return \Blacksheep\Brand\Api\Data\BrandExtensionInterface
     */
    public function getExtensionAttributes(): \Blacksheep\Brand\Api\Data\BrandExtensionInterface;
}
