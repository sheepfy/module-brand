<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\View\Asset\Image;

use Blacksheep\Brand\Model\Brand\Media\ConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\View\Asset\ContextInterface;

class Context implements ContextInterface
{
    private WriteInterface $mediaDirectory;

    public function __construct(
        private ConfigInterface $mediaConfig,
        Filesystem $filesystem
    ) {
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->mediaDirectory->create($this->mediaConfig->getBaseMediaPath());
    }

    public function getPath()
    {
        return $this->mediaDirectory->getAbsolutePath($this->mediaConfig->getBaseMediaPath());
    }

    public function getBaseUrl()
    {
        return $this->mediaConfig->getBaseMediaUrl();
    }
}
