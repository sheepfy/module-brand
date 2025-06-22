<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Model\Brand\Media;

interface ConfigInterface
{
    public function getBaseMediaUrl(): string;

    public function getBaseMediaPath(): string;

    public function getMediaUrl(string $file): string;

    public function getMediaPath(string $file): string;
}
